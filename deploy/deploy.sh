#!/usr/bin/env bash
# Deploys DAMIS on the midas.lt server (Ubuntu 24.04+, Apache with mod_php, local MySQL), without Docker:
# the GUI (this repository), the computing service (DAMIS_WebService) and the algorithms (DAMIS).
#
#   sudo deploy/deploy.sh             update the three repositories to origin/master, then deploy
#   sudo deploy/deploy.sh --no-pull   deploy the code as it is checked out
#
# The three repositories are sibling checkouts (<dir>/DAMIS-GUI, <dir>/DAMIS_WebService, <dir>/DAMIS);
# missing ones are cloned from github.com/InScience. Result:
#   GUI         the existing Apache virtual hosts that serve DAMIS-GUI/public (plus one Include line)
#   service     /usr/lib/cgi-bin/DamisService.cgi, as CGI on http://localhost:8080 only (apache/damis-service.conf)
#   algorithms  /opt/damis/bin/Algorithms
#   statistics  AWStats, every hour, into /var/www/awstats (shown to administrators under /admin/statistics)
# Every run first backs up the database, /usr/lib/cgi-bin, the Apache sites and the GUI configuration into
# /var/backups/damis/<timestamp>/ (see README.md, "Deployment", for a rollback), and ends with the SOAP smoke test.
# With SMOKE_USER and SMOKE_PASSWORD (an administrator) set, tests/smoke/smoke.sh also runs against
# SMOKE_BASE_URL (default https://damis.midas.lt).
set -euo pipefail

GUI_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
SRC_DIR=$(dirname "$GUI_DIR")
APP_USER=$(stat -c %U "$GUI_DIR")
WEB_USER=www-data
BRANCH=${DAMIS_BRANCH:-master}

SERVICE_URL=http://localhost:8080/cgi-bin/DamisService.cgi
DATA_URL=http://localhost:8080/DamisData/
DATA_DIR=/var/lib/damis/data/
ALGORITHMS=/opt/damis/bin/Algorithms
AWSTATS_OUT=/var/www/awstats
SITE_DOMAIN=${DAMIS_SITE_DOMAIN:-damis.midas.lt}
BACKUP_DIR=/var/backups/damis/$(date +%Y%m%d-%H%M%S)
PACKAGES="git build-essential openmpi-bin libopenmpi-dev gsoap libgsoap-dev libcurl4-openssl-dev awstats apache2-utils"

log() { echo "[deploy] $*"; }
die() { echo "[deploy] ERROR: $*" >&2; exit 1; }
as_user() { sudo -u "$APP_USER" -H "$@"; }
# console commands run as www-data, the owner of var/ (cron runs experiment:execute as www-data)
console() { (cd "$GUI_DIR" && sudo -u "$WEB_USER" php bin/console --no-interaction "$@"); }
# commands that write into public/ and web/ (owned by the checkout user) run as that user, with a throwaway
# cache and log directory so var/ stays www-data's
user_console() {
    (cd "$GUI_DIR" && as_user env APP_CACHE_DIR="$USER_CACHE" APP_LOG_DIR="$USER_CACHE" php bin/console --no-interaction "$@")
}
set_env() { # KEY VALUE in .env.local
    if grep -q "^$1=" "$GUI_DIR/.env.local" 2>/dev/null; then
        as_user sed -i "s#^$1=.*#$1=$2#" "$GUI_DIR/.env.local"
    else
        echo "$1=$2" | as_user tee -a "$GUI_DIR/.env.local" > /dev/null
    fi
}

preflight() {
    [ "$EUID" -eq 0 ] || die "run with sudo"
    [ -f "$GUI_DIR/config/services.yml" ] || die "$GUI_DIR/config/services.yml is missing (copy config/services.yml.dist and fill it in)"
    if pgrep -u "$WEB_USER" -f 'bin/console experiment:execute' > /dev/null; then
        die "an experiment is running right now (experiment:execute), deploy later"
    fi

    local missing="" p
    for p in $PACKAGES; do dpkg -s "$p" > /dev/null 2>&1 || missing="$missing $p"; done
    if [ -n "$missing" ]; then
        log "installing packages:$missing"
        apt-get update -q
        # shellcheck disable=SC2086 # one argument per package
        DEBIAN_FRONTEND=noninteractive apt-get install -y -q --no-install-recommends $missing
    fi

    # Babel 8 / Encore 6 (package.json) need Node.js 22.18+; Ubuntu 24.04 ships 18
    local node
    node=$(node -v 2>/dev/null || echo v0)
    [ "$(printf '%s\n' 22.18.0 "${node#v}" | sort -V | head -1)" = 22.18.0 ] \
        || die "Node.js >= 22.18 required, found $node (install Node.js 22 LTS, e.g. from deb.nodesource.com)"
    command -v composer > /dev/null || die "composer not found"
}

backup() {
    log "backup into $BACKUP_DIR"
    install -d -m 700 "$BACKUP_DIR"
    DB_NAME=$(sed -nE "s/^\s*database_name:\s*['\"]?([^'\" ]+).*/\1/p" "$GUI_DIR/config/services.yml" | head -1)
    [ -n "$DB_NAME" ] || die "database_name not found in config/services.yml"
    # root connects through the MySQL socket (auth_socket), no password needed
    mysqldump --single-transaction --routines --no-tablespaces "$DB_NAME" | gzip > "$BACKUP_DIR/$DB_NAME.sql.gz"
    tar -C /usr/lib -czf "$BACKUP_DIR/cgi-bin.tar.gz" cgi-bin
    tar -C /etc/apache2 -czf "$BACKUP_DIR/apache2-sites.tar.gz" sites-available
    cp -a "$GUI_DIR/config/services.yml" "$BACKUP_DIR/"
    [ ! -f "$GUI_DIR/.env.local" ] || cp -a "$GUI_DIR/.env.local" "$BACKUP_DIR/env.local"
    local repo
    for repo in DAMIS-GUI DAMIS_WebService DAMIS; do
        [ ! -d "$SRC_DIR/$repo/.git" ] || echo "$repo $(git -C "$SRC_DIR/$repo" rev-parse HEAD)" >> "$BACKUP_DIR/revisions.txt"
    done
}

update_code() {
    local repo
    for repo in DAMIS-GUI DAMIS_WebService DAMIS; do
        if [ ! -d "$SRC_DIR/$repo/.git" ]; then
            log "cloning $repo"
            as_user git clone --branch "$BRANCH" "https://github.com/InScience/$repo.git" "$SRC_DIR/$repo"
        elif [ "$PULL" = 1 ]; then
            log "updating $repo"
            as_user git -C "$SRC_DIR/$repo" pull --ff-only origin "$BRANCH"
        fi
        log "$repo at $(git -C "$SRC_DIR/$repo" log -1 --format='%h %s')"
    done
}

deploy_gui() {
    log "GUI: configuration"
    cd "$GUI_DIR"
    # parameter added in 2026 (config/services.yml.dist): twig globals need it under "parameters" as well
    if [ "$(grep -c 'midas_url:' config/services.yml)" -lt 2 ]; then
        as_user sed -i "/^\s*use_ssl_connections:/a\    midas_url: 'https://www.midas.lt'" config/services.yml
    fi
    set_env DAMIS_SERVICE_WSDL "$SERVICE_URL?wsdl"
    # the service downloads datasets over plain http from this server (port 80 does not redirect /uploads/)
    set_env DAMIS_DATA_HOST http://127.0.0.1
    set_env DAMIS_AWSTATS_DIR "$AWSTATS_OUT"

    # public/ holds symlinks into web/ (older installations have copies, moved to the backup)
    local item
    for item in css js fonts docs favicon.ico robots.txt damis.wsdl; do
        if [ -e "public/$item" ] && [ ! -L "public/$item" ]; then mv "public/$item" "$BACKUP_DIR/public-$item"; fi
        [ -L "public/$item" ] || as_user ln -s "../web/$item" "public/$item"
    done

    local app_env no_dev=--no-dev
    app_env=$(grep -hs '^APP_ENV=' .env .env.local | tail -1 | cut -d= -f2)
    [ "$app_env" = prod ] || { log "APP_ENV is '$app_env', not prod: installing dev dependencies too"; no_dev=; }
    log "GUI: composer install"
    as_user composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts $no_dev
    log "GUI: frontend build"
    as_user npm ci --no-audit --no-fund
    as_user npx encore production

    log "GUI: database migrations"
    [ "$(mysql -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME' AND table_name = 'doctrine_migration_versions'")" = 1 ] \
        || die "table doctrine_migration_versions missing in $DB_NAME, migrations were never synchronised (see docker/php/entrypoint.sh, step 5)"
    # earlier manual runs may have left files of other users in var/
    rm -rf var/cache/*
    chown -R "$WEB_USER:$WEB_USER" var
    install -d -o "$WEB_USER" -g "$WEB_USER" public/uploads public/uploads/datasets
    console doctrine:migrations:migrate --allow-no-migration

    USER_CACHE=$(as_user mktemp -d)
    user_console assets:install public --symlink --relative
    user_console fos:js-routing:dump --format=js --target=public/js/fos_js_routes.js
    rm -rf "$USER_CACHE"
    console cache:clear
}

deploy_service() {
    log "algorithms: build"
    as_user make -C "$SRC_DIR/DAMIS" -j"$(nproc)"
    make -C "$SRC_DIR/DAMIS" install PREFIX="$(dirname "$(dirname "$ALGORITHMS")")"
    log "service: build"
    as_user make -C "$SRC_DIR/DAMIS_WebService" -j"$(nproc)"

    # the old standalone service (port 8080, c++_projects/DamisService2) is replaced by the CGI
    if systemctl is-enabled --quiet damis.service 2> /dev/null || systemctl is-active --quiet damis.service 2> /dev/null; then
        log "service: disabling the old damis.service"
        systemctl disable --now damis.service
    fi
    make -C "$SRC_DIR/DAMIS_WebService" install PREFIX=/usr/lib/cgi-bin

    log "service: configuration"
    install -d -o "$WEB_USER" -g "$WEB_USER" "$DATA_DIR" "${DATA_DIR}err" /var/log/damis
    install -d /etc/damis
    sed "s#<SOAP:address location=\"[^\"]*\"#<SOAP:address location=\"$SERVICE_URL\"#" \
        "$SRC_DIR/DAMIS_WebService/wsdl/damis.wsdl" > /etc/damis/damis.wsdl
    # easylogging++; never TO_STANDARD_OUTPUT (stdout is the SOAP response)
    cat > /etc/damis/logging.conf <<EOF
* GLOBAL:
    FORMAT = "%datetime %level %msg"
    FILENAME = "/var/log/damis/damis-service.log"
    ENABLED = true
    TO_FILE = true
    TO_STANDARD_OUTPUT = false
    MAX_LOG_FILE_SIZE = 10485760
EOF
    # read by DamisService.cgi from its own directory (same keys as DAMIS_WebService/docker/entrypoint.sh)
    cat > /usr/lib/cgi-bin/settings.csv <<EOF
% generated by DAMIS-GUI/deploy/deploy.sh - do not edit, changes are overwritten on the next deploy
wsdlPath /etc/damis/damis.wsdl
logingConfFilePath /etc/damis/logging.conf
pathToAlgorithmsExe $ALGORITHMS
pathToMPIExecutable mpirun
mpiOptions --oversubscribe --bind-to none
noOfProcessors $(nproc)
maxRunTime 3300
localDataFileSavePath $DATA_DIR
workingDirMPI $DATA_DIR
httpDataFileSavePath $DATA_URL
dataFileExtension .arff
lenghtOfTmpFileName 20
keepFilesInDirectory 172800
EOF
    chmod 644 /usr/lib/cgi-bin/settings.csv /etc/damis/damis.wsdl /etc/damis/logging.conf
}

deploy_awstats() {
    log "statistics: AWStats"
    install -d /var/lib/awstats "$AWSTATS_OUT" /var/log/damis-apache
    sed "s#__SITE_DOMAIN__#$SITE_DOMAIN#g" "$GUI_DIR/docker/awstats/awstats.damis.conf.template" > /etc/awstats/awstats.damis.conf
    install -m 0755 "$GUI_DIR/docker/awstats/damis-awstats-update" /usr/local/bin/damis-awstats-update
    # the package's own job would process awstats.damis.conf too, as www-data
    rm -f /etc/cron.d/awstats
    echo "5 * * * * root /usr/local/bin/damis-awstats-update >> /var/log/damis-awstats.log 2>&1" > /etc/cron.d/damis-awstats
}

deploy_apache() {
    log "apache"
    install -m 0644 "$GUI_DIR/deploy/apache/damis-gui-vhost.conf" /etc/apache2/damis-gui-vhost.conf
    install -m 0644 "$GUI_DIR/deploy/apache/damis-service.conf" /etc/apache2/sites-available/damis-service.conf
    local site
    for site in /etc/apache2/sites-available/*.conf; do
        grep -q 'DAMIS-GUI/public' "$site" || continue
        grep -q 'damis-gui-vhost.conf' "$site" \
            || sed -i '/<\/VirtualHost>/i\    Include /etc/apache2/damis-gui-vhost.conf' "$site"
    done
    # serve-cgi-bin would publish /usr/lib/cgi-bin (the service, awstats.pl) on the public site
    a2disconf -q serve-cgi-bin 2> /dev/null || true
    a2enmod -q cgi rewrite alias setenvif
    a2ensite -q damis-service
    apache2ctl configtest
    systemctl reload apache2
}

smoke_test() {
    log "smoke test: DamisService"
    local i
    for i in $(seq 10); do
        # not grep -q: it exits at the first match, curl then fails writing the rest and pipefail fails the check
        curl -sf "$SERVICE_URL?wsdl" | grep '<definitions' > /dev/null && break
        [ "$i" -lt 10 ] || die "$SERVICE_URL?wsdl does not answer"
        sleep 1
    done
    (cd "$GUI_DIR" && sudo -u "$WEB_USER" env DAMIS_SERVICE_WSDL="$SERVICE_URL?wsdl" DAMIS_DATA_HOST=http://127.0.0.1 \
        php tests/smoke/damis-service.php)
    if [ -n "${SMOKE_USER:-}" ] && [ -n "${SMOKE_PASSWORD:-}" ]; then
        log "smoke test: GUI"
        (cd "$GUI_DIR" && sudo -u "$WEB_USER" env SMOKE_USER="$SMOKE_USER" SMOKE_PASSWORD="$SMOKE_PASSWORD" \
            SMOKE_BASE_URL="${SMOKE_BASE_URL:-https://$SITE_DOMAIN}" bash tests/smoke/smoke.sh)
    else
        log "GUI smoke test skipped (set SMOKE_USER and SMOKE_PASSWORD of an administrator to run it)"
    fi
}

# everything in functions: "git pull" may replace this file while it runs
main() {
    PULL=1
    [ "${1:-}" != --no-pull ] || PULL=0
    preflight
    backup
    # no experiment:execute runs while the code and the service change
    systemctl stop cron
    trap 'systemctl start cron' EXIT
    update_code
    deploy_gui
    deploy_service
    deploy_awstats
    deploy_apache
    systemctl start cron
    trap - EXIT
    smoke_test
    log "done, backup in $BACKUP_DIR"
}

main "$@"

#!/usr/bin/env bash
# One-time switch of damis.midas.lt from the host installation (Apache with mod_php, MySQL, DamisService as CGI,
# set up by the earlier non-Docker deploy script) to the Docker stack (docker-compose.yml + docker-compose.prod.yml).
#
#   sudo deploy/switch-to-docker.sh
#
# Needs Docker Engine with the compose plugin (README.md, "Deployment"). While the site still runs, it creates
# DAMIS-GUI/.env (random passwords, the certificate of the Apache https site), builds the images, the PHP
# dependencies and the frontend. Then (downtime, a few minutes) it stops Apache, the www-data crontab and MySQL,
# imports a fresh database dump into the mysql container and starts the stack. If anything fails after Apache was
# stopped, the host installation is started again automatically. The host services are disabled, not removed:
# README.md describes the way back. Later updates: deploy/deploy.sh.
set -euo pipefail

GUI_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
SRC_DIR=$(dirname "$GUI_DIR")
APP_USER=$(stat -c %U "$GUI_DIR")
SITE_DOMAIN=${DAMIS_SITE_DOMAIN:-damis.midas.lt}
SSL_SITE=${DAMIS_SSL_SITE:-/etc/apache2/sites-available/damis-gui-ssl.conf}
BACKUP_DIR=/var/backups/damis/$(date +%Y%m%d-%H%M%S)-switch
IMPORT=$GUI_DIR/docker/mysql/import/damis.sql.gz

log() { echo "[switch] $*"; }
die() { echo "[switch] ERROR: $*" >&2; exit 1; }
as_user() { sudo -u "$APP_USER" -H "$@"; }
dc() { (cd "$GUI_DIR" && docker compose "$@"); }
set_env() { sed -i "s#^$1=.*#$1=$2#" "$GUI_DIR/.env"; }
# path from an Apache directive in the https site, e.g. SSLCertificateFile "/root/cert/midas.lt.crt"
ssl_file() { sed -nE "s/^\s*$1\s+\"?([^\" ]+)\"?\s*$/\1/p" "$SSL_SITE" | head -1; }

preflight() {
    [ "$EUID" -eq 0 ] || die "run with sudo"
    docker compose version > /dev/null 2>&1 \
        || die "Docker with the compose plugin is not installed (README.md, Deployment)"
    [ ! -e "$GUI_DIR/.env" ] || die "$GUI_DIR/.env exists: the stack is set up already, updates go through deploy/deploy.sh"
    [ -f "$GUI_DIR/config/services.yml" ] || die "$GUI_DIR/config/services.yml is missing"
    local repo
    for repo in DAMIS_WebService DAMIS; do
        [ -d "$SRC_DIR/$repo/.git" ] || die "$SRC_DIR/$repo is missing (git clone https://github.com/InScience/$repo.git)"
    done
    systemctl is-active --quiet apache2 || die "Apache is not running: nothing to switch from"
    if pgrep -f 'bin/console experiment:execute' > /dev/null; then
        die "an experiment is running right now (experiment:execute), switch later"
    fi

    DB_NAME=$(sed -nE "s/^\s*database_name:\s*['\"]?([^'\" ]+).*/\1/p" "$GUI_DIR/config/services.yml" | head -1)
    [ -n "$DB_NAME" ] || die "database_name not found in config/services.yml"
    [ -f "$SSL_SITE" ] || die "$SSL_SITE not found (set DAMIS_SSL_SITE to the Apache https site of $SITE_DOMAIN)"
    CERT=$(ssl_file SSLCertificateFile)
    KEY=$(ssl_file SSLCertificateKeyFile)
    CHAIN=$(ssl_file SSLCertificateChainFile)
    local f
    for f in "$CERT" "$KEY" "$CHAIN"; do
        [ -n "$f" ] && [ -f "$f" ] || die "certificate file '$f' from $SSL_SITE not found"
    done
}

backup() {
    log "backup into $BACKUP_DIR"
    install -d -m 700 "$BACKUP_DIR"
    mysqldump --single-transaction --routines --no-tablespaces "$DB_NAME" | gzip > "$BACKUP_DIR/$DB_NAME.sql.gz"
    tar -C "$GUI_DIR/public" -czf "$BACKUP_DIR/uploads.tar.gz" uploads
    cp -a "$GUI_DIR/config/services.yml" "$BACKUP_DIR/"
    crontab -u www-data -l > "$BACKUP_DIR/crontab-www-data" 2> /dev/null || rm -f "$BACKUP_DIR/crontab-www-data"
    local repo
    for repo in DAMIS-GUI DAMIS_WebService DAMIS; do
        echo "$repo $(git -C "$SRC_DIR/$repo" rev-parse HEAD)" >> "$BACKUP_DIR/revisions.txt"
    done
}

# the site still runs on the host meanwhile
prepare() {
    # a failure here leaves the host installation untouched; without .env the script can simply run again
    trap 'mv "$GUI_DIR/.env" "$BACKUP_DIR/env.failed" 2> /dev/null || true' EXIT
    log "configuration: $GUI_DIR/.env"
    install -m 600 -o "$APP_USER" -g "$(id -gn "$APP_USER")" "$GUI_DIR/deploy/docker.env.example" "$GUI_DIR/.env"
    set_env SITE_DOMAIN "$SITE_DOMAIN"
    set_env SSL_CERTIFICATE "$CERT"
    set_env SSL_CERTIFICATE_KEY "$KEY"
    set_env SSL_CERTIFICATE_CHAIN "$CHAIN"
    set_env DOCKER_UID "$(id -u "$APP_USER")"
    set_env DOCKER_GID "$(id -g "$APP_USER")"
    set_env MYSQL_DATABASE "$DB_NAME"
    local key
    for key in APP_SECRET MYSQL_PASSWORD MYSQL_ROOT_PASSWORD; do set_env "$key" "$(openssl rand -hex 24)"; done

    log "building the images"
    dc build --pull
    # same lock file and APP_ENV=prod as the host installation, so the running site is not affected
    log "PHP dependencies and frontend"
    dc run --rm --no-deps -u www-data php \
        composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts --no-dev
    dc run --rm --no-deps -u www-data php sh -c 'npm ci --no-audit --no-fund && npx encore production'
    trap - EXIT
}

# back to the host installation (its database was only read)
undo() {
    echo "[switch] FAILED, starting the host installation again" >&2
    dc logs --tail=100 || true
    dc down -v || true
    rm -f "$IMPORT"
    mv "$GUI_DIR/.env" "$BACKUP_DIR/env.failed" 2> /dev/null || true
    [ ! -f "$BACKUP_DIR/env.local" ] || cp -a "$BACKUP_DIR/env.local" "$GUI_DIR/.env.local"
    chown -R www-data:www-data "$GUI_DIR/var" "$GUI_DIR/public/uploads"
    [ ! -f "$BACKUP_DIR/crontab-www-data" ] || crontab -u www-data "$BACKUP_DIR/crontab-www-data"
    [ ! -f "$BACKUP_DIR/cron.d-damis-awstats" ] || cp -a "$BACKUP_DIR/cron.d-damis-awstats" /etc/cron.d/damis-awstats
    systemctl enable --now mysql apache2
    echo "[switch] host installation running again; details above, backup in $BACKUP_DIR" >&2
}

switch() {
    trap undo EXIT
    log "stopping the host installation (downtime starts)"
    crontab -u www-data -r 2> /dev/null || true
    [ ! -f /etc/cron.d/damis-awstats ] || mv /etc/cron.d/damis-awstats "$BACKUP_DIR/cron.d-damis-awstats"
    systemctl disable --now apache2
    log "database dump"
    mysqldump --single-transaction --routines --no-tablespaces "$DB_NAME" | gzip > "$IMPORT"
    cp "$IMPORT" "$BACKUP_DIR/$DB_NAME-at-switch.sql.gz"
    chmod 644 "$IMPORT" # read by the mysql user of the container
    systemctl disable --now mysql

    # .env.local had the host addresses of the service; the containers get theirs from docker-compose.yml
    [ ! -f "$GUI_DIR/.env.local" ] || mv "$GUI_DIR/.env.local" "$BACKUP_DIR/env.local"
    rm -rf "$GUI_DIR/var/cache"
    # www-data in the containers has the uid of the checkout owner
    chown -R "$APP_USER:$(id -gn "$APP_USER")" "$GUI_DIR/var" "$GUI_DIR/public/uploads"

    log "database import into the mysql container"
    dc up -d mysql
    local i
    for i in $(seq 120); do
        [ "$(dc ps --format '{{.Health}}' mysql)" = healthy ] && break
        [ "$i" -lt 120 ] || die "the mysql container did not become healthy"
        sleep 5
    done
    rm -f "$IMPORT"
    # cron jobs (/cron page) call bin/console by its path on the host
    # shellcheck disable=SC2016 # MYSQL_* expanded by the shell in the container
    dc exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot "$MYSQL_DATABASE"' <<SQL
UPDATE cron_job SET command = REPLACE(REPLACE(command, '$GUI_DIR/', '/var/www/html/'), '/var/www/DAMIS-GUI/', '/var/www/html/');
SQL

    log "starting the stack"
    dc up -d
    for i in $(seq 120); do
        # not grep -q: with pipefail, docker compose logs failing on the closed pipe would hide the match
        dc logs php | grep '\[damis\] Ready' > /dev/null && break
        [ "$(dc ps --format '{{.State}}' php)" = running ] || die "the php container stopped"
        [ "$i" -lt 120 ] || die "the application did not start"
        sleep 5
    done
    smoke_test
    trap - EXIT
}

smoke_test() {
    log "smoke test: DamisService"
    dc exec -T -u www-data php php tests/smoke/damis-service.php
    log "smoke test: https://$SITE_DOMAIN through the container"
    local code i
    for i in $(seq 15); do
        # a curl failure (TLS, certificate) must not end the script under set -e before the retries
        code=$(curl -s -o /dev/null -w '%{http_code}' --resolve "$SITE_DOMAIN:443:127.0.0.1" "https://$SITE_DOMAIN/") \
            || code="curl error $? (see man curl, EXIT CODES)"
        [[ "$code" =~ ^(200|302)$ ]] && break
        [ "$i" -lt 15 ] || die "https://$SITE_DOMAIN/ answered $code"
        sleep 2
    done
}

main() {
    preflight
    backup
    prepare
    switch
    log "done: the site runs in Docker (sudo docker compose ps in $GUI_DIR), backup in $BACKUP_DIR"
}

main "$@"

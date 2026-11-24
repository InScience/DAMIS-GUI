#!/usr/bin/env bash
# Updates DAMIS on the midas.lt server, which runs the Docker stack (docker-compose.yml + docker-compose.prod.yml,
# set up once by deploy/switch-to-docker.sh): the GUI (this repository), the computing service (DAMIS_WebService)
# and the algorithms (DAMIS).
#
#   sudo deploy/deploy.sh             update the three repositories to origin/master, then deploy
#   sudo deploy/deploy.sh --no-pull   deploy the code as it is checked out
#
# The three repositories are sibling checkouts (<dir>/DAMIS-GUI, <dir>/DAMIS_WebService, <dir>/DAMIS); missing
# ones are cloned from github.com/InScience. Every run first backs up the database, the uploaded files and the
# configuration into /var/backups/damis/<timestamp>/ (deploy/backup.sh, also installed as a daily cron job), and
# ends with smoke tests (https, DamisService over SOAP).
# With SMOKE_USER and SMOKE_PASSWORD (an administrator) in the environment, tests/smoke/smoke.sh runs as well.
set -euo pipefail

GUI_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
SRC_DIR=$(dirname "$GUI_DIR")
APP_USER=$(stat -c %U "$GUI_DIR")
BRANCH=${DAMIS_BRANCH:-master}
BACKUP_DIR=/var/backups/damis/$(date +%Y%m%d-%H%M%S)

log() { echo "[deploy] $*"; }
die() { echo "[deploy] ERROR: $*" >&2; exit 1; }
as_user() { sudo -u "$APP_USER" -H "$@"; }
dc() { (cd "$GUI_DIR" && docker compose "$@"); }

preflight() {
    [ "$EUID" -eq 0 ] || die "run with sudo"
    docker compose version > /dev/null 2>&1 || die "Docker with the compose plugin is not installed"
    grep -q '^COMPOSE_FILE=.*docker-compose.prod.yml' "$GUI_DIR/.env" 2> /dev/null \
        || die "$GUI_DIR/.env with the production settings is missing (first installation: deploy/switch-to-docker.sh)"
    # processes of the containers are visible on the host
    if pgrep -f 'bin/console experiment:execute' > /dev/null; then
        die "an experiment is running right now (experiment:execute), deploy later"
    fi
}

backup() {
    log "backup into $BACKUP_DIR"
    "$GUI_DIR/deploy/backup.sh" "$BACKUP_DIR"
    # daily backup of the running site (deploy/backup.sh)
    printf '%s\n' '# DAMIS: daily backup (database, uploads, configuration), installed by deploy/deploy.sh' \
        "15 3 * * * root $GUI_DIR/deploy/backup.sh >> /var/log/damis-backup.log 2>&1" > /etc/cron.d/damis-backup
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

deploy() {
    # images first (new base image versions, DamisService and the algorithms rebuilt from the checkouts)
    log "building the images"
    dc build --pull
    # no new experiment starts while the code changes (cron starts again with the php container)
    # shellcheck disable=SC2016 # expanded by the shell in the container
    dc exec -T php sh -c 'kill "$(cat /var/run/crond.pid)"' 2> /dev/null || true
    log "PHP dependencies and frontend"
    dc run --rm --no-deps -u www-data php \
        composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts --no-dev
    dc run --rm --no-deps -u www-data php sh -c 'npm ci --no-audit --no-fund && npx encore production'
    rm -rf "$GUI_DIR/var/cache"

    log "starting the containers"
    dc up -d --remove-orphans
    # the php entrypoint migrates the database, installs the assets, dumps the JS routes and writes the crontab:
    # restart it also when its container was not recreated
    local since i
    since=$(date -u +%Y-%m-%dT%H:%M:%SZ)
    dc restart php
    for i in $(seq 120); do
        # not grep -q: with pipefail, docker compose logs failing on the closed pipe would hide the match
        dc logs --since "$since" php | grep '\[damis\] Ready' > /dev/null && break
        [ "$(dc ps --format '{{.State}}' php)" = running ] || { dc logs --tail=50 php; die "the php container stopped"; }
        [ "$i" -lt 120 ] || { dc logs --tail=50 php; die "the application did not start"; }
        sleep 5
    done
}

smoke_test() {
    local site code i
    site=$(sed -n 's/^SITE_DOMAIN=//p' "$GUI_DIR/.env")
    log "smoke test: https://$site through the container"
    # php-fpm starts listening a moment after the entrypoint's "Ready"
    for i in $(seq 15); do
        # a curl failure (TLS, certificate) must not end the script under set -e before the retries
        code=$(curl -s -o /dev/null -w '%{http_code}' --resolve "$site:443:127.0.0.1" "https://$site/") \
            || code="curl error $? (see man curl, EXIT CODES)"
        [[ "$code" =~ ^(200|302)$ ]] && break
        [ "$i" -lt 15 ] || die "https://$site/ answered $code"
        sleep 2
    done
    log "smoke test: DamisService"
    dc exec -T -u www-data php php tests/smoke/damis-service.php
    if [ -n "${SMOKE_USER:-}" ] && [ -n "${SMOKE_PASSWORD:-}" ]; then
        log "smoke test: GUI"
        # credentials passed by name (-e NAME), never as values on a command line; http://apache is not redirected
        dc exec -T -u www-data -e SMOKE_USER -e SMOKE_PASSWORD -e SMOKE_BASE_URL=http://apache php bash tests/smoke/smoke.sh
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
    update_code
    deploy
    smoke_test
    log "done, backup in $BACKUP_DIR"
}

main "$@"

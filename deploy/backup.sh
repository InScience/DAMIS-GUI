#!/usr/bin/env bash
# Backs up DAMIS running in Docker: the database (dump from the mysql container), the uploaded files, the
# configuration and the revisions of the three repositories.
#
#   sudo deploy/backup.sh         daily backup into /var/backups/damis/daily/<timestamp>/; daily backups older than
#                                 BACKUP_KEEP_DAYS (default 30) days are removed afterwards
#   sudo deploy/backup.sh DIR     backup into DIR, nothing removed (used by deploy/deploy.sh)
#
# deploy/deploy.sh installs /etc/cron.d/damis-backup, which runs the daily backup at 03:15 (log:
# /var/log/damis-backup.log). Restore: see README.md, "Deployment".
set -euo pipefail

GUI_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
SRC_DIR=$(dirname "$GUI_DIR")
DAILY_DIR=/var/backups/damis/daily
KEEP_DAYS=${BACKUP_KEEP_DAYS:-30}
DIR=${1:-$DAILY_DIR/$(date +%Y%m%d-%H%M%S)}

log() { echo "[backup] $(date '+%F %T') $*"; }
dc() { (cd "$GUI_DIR" && docker compose "$@"); }

# an incomplete backup must not look like a good one
trap 'log "FAILED, removing $DIR"; rm -rf "$DIR"' ERR

install -d -m 700 "$DIR"
# the password stays inside the container (MYSQL_PWD), not on a command line
# shellcheck disable=SC2016 # expanded by the shell in the container
dc exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysqldump -uroot --single-transaction --routines --no-tablespaces "$MYSQL_DATABASE"' \
    | gzip > "$DIR/database.sql.gz"
# mysqldump ends a complete dump with this line
[ "$(gzip -dc "$DIR/database.sql.gz" | tail -n 1 | cut -c 1-17)" = "-- Dump completed" ]
tar -C "$GUI_DIR/public" -czf "$DIR/uploads.tar.gz" uploads
cp -a "$GUI_DIR/config/services.yml" "$DIR/"
[ ! -f "$GUI_DIR/.env" ] || cp -a "$GUI_DIR/.env" "$DIR/env"
for repo in DAMIS-GUI DAMIS_WebService DAMIS; do
    # root from cron (no SUDO_UID) reading the user's checkout: git refuses it without safe.directory
    [ ! -d "$SRC_DIR/$repo/.git" ] \
        || echo "$repo $(git -c safe.directory="$SRC_DIR/$repo" -C "$SRC_DIR/$repo" rev-parse HEAD)" >> "$DIR/revisions.txt"
done
log "backup in $DIR ($(du -sh "$DIR" | cut -f 1))"

if [ $# -eq 0 ]; then
    find "$DAILY_DIR" -mindepth 1 -maxdepth 1 -type d -mtime +"$KEEP_DAYS" -print -exec rm -rf {} +
fi

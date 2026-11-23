#!/usr/bin/env bash
# Seeds the DAMIS database on the very first start of the mysql container (empty data volume).
# Dumps placed in docker/mysql/import/ (*.sql, *.sql.gz) take precedence over the sample data.
set -euo pipefail
shopt -s nullglob

# Explicit charset: without a locale the client falls back to latin1 and double-encodes the UTF-8 files
db() { mysql --protocol=socket --default-character-set=utf8mb4 -uroot -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}"; }

dumps=(/damis/import/*.sql /damis/import/*.sql.gz)

if (( ${#dumps[@]} )); then
    for dump in "${dumps[@]}"; do
        echo "[damis] Importing $dump"
        case "$dump" in
            *.gz) gunzip -c "$dump" | db ;;
            *) db < "$dump" ;;
        esac
    done
else
    echo "[damis] Importing sample data from sql/"
    db < /damis/sql/sql_structure.sql
    db < /damis/sql/data.sql
fi

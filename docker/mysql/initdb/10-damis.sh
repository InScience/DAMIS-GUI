#!/usr/bin/env bash
# Seeds the DAMIS database on the very first start of the mysql container (empty data volume).
# Dumps placed in docker/mysql/import/ (*.sql, *.sql.gz) take precedence over the sample data.
set -euo pipefail
shopt -s nullglob

# Explicit charset: without a locale the client falls back to latin1 and double-encodes the UTF-8 files
db() { MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" mysql --protocol=socket --default-character-set=utf8mb4 -uroot "${MYSQL_DATABASE}"; }

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

# Empty database for the PHPUnit tests (APP_ENV=test uses <database>_test, the schema is created by the tests)
echo "[damis] Creating test database ${MYSQL_DATABASE}_test"
db <<SQL
CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}_test\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE}_test\`.* TO '${MYSQL_USER}'@'%';
SQL

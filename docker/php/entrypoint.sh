#!/usr/bin/env bash
# Bootstraps the DAMIS application inside the php container, then starts php-fpm.
# Every step is idempotent, so restarts only redo what is missing.
set -euo pipefail

cd /var/www/html

# Anything other than the default command (e.g. `docker compose run php bash`) skips the bootstrap
if [ "${1:-}" != "php-fpm" ]; then
    exec "$@"
fi

as_app() { setpriv --reuid=www-data --regid=www-data --init-groups -- "$@"; }
console() { as_app php bin/console --no-interaction "$@"; }
log() { echo "[damis] $*"; }

# 1. Application parameters (config/services.yml is gitignored, generated from the .dist template)
if [ ! -f config/services.yml ]; then
    log "Generating config/services.yml from config/services.yml.dist"
    sed -E \
        -e "s|^(\s*database_host:).*|\1 '${DATABASE_HOST}'|" \
        -e "s|^(\s*database_port:).*|\1 ${DATABASE_PORT}|" \
        -e "s|^(\s*database_name:).*|\1 '${DATABASE_NAME}'|" \
        -e "s|^(\s*database_user:).*|\1 '${DATABASE_USER}'|" \
        -e "s|^(\s*database_password:).*|\1 '${DATABASE_PASSWORD}'|" \
        -e "s|^(\s*project_domain:).*|\1 '${PROJECT_DOMAIN}'|" \
        -e "s|^(\s*project_full_host:).*|\1 '${PROJECT_FULL_HOST}'|" \
        config/services.yml.dist | as_app tee config/services.yml > /dev/null
fi

# 2. Web root: public/ is gitignored, static assets live in web/
as_app mkdir -p var public/uploads/datasets public/uploads/algorithms
for item in css js fonts docs favicon.ico robots.txt damis.wsdl; do
    if [ ! -e "public/$item" ] && [ ! -L "public/$item" ]; then
        as_app ln -s "../web/$item" "public/$item"
    fi
done

# 3. PHP dependencies (post-install scripts also clear the cache and install bundle assets)
if [ ! -f vendor/autoload_runtime.php ]; then
    log "Installing composer dependencies"
    as_app composer install --no-interaction --prefer-dist
fi

# 4. Webpack Encore build (framework.assets requires public/build/manifest.json)
if [ ! -f public/build/manifest.json ]; then
    log "Building frontend assets"
    as_app npm ci --no-audit --no-fund
    as_app npx encore production
fi

# 5. Database schema and data
# (information_schema lookup instead of a failing query, which Symfony would log as CRITICAL)
table_exists() {
    local out
    out=$(console dbal:run-sql "SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '$1'")
    grep -qE '^\s*1\s*$' <<< "$out"
}
if ! table_exists doctrine_migration_versions; then
    if table_exists users; then
        # Legacy schema (sql/sql_structure.sql + sql/data.sql, or a production dump) seeded by
        # the mysql container: bring it up to the current entity mapping.
        log "Upgrading legacy database schema"
        console app:fix-user-roles
        console doctrine:schema:update --force
        console doctrine:migrations:sync-metadata-storage
        # These migrations only contain schema changes that schema:update has already applied
        console doctrine:migrations:version 'DoctrineMigrations\Version20251030003914' --add
        console doctrine:migrations:version 'DoctrineMigrations\Version20251206215300' --add
    else
        log "Creating empty database schema"
        console doctrine:schema:create
        console doctrine:migrations:sync-metadata-storage
        console doctrine:migrations:version --add --all
    fi
fi
console doctrine:migrations:migrate --allow-no-migration

# 6. Generated assets
if [ ! -d public/bundles ]; then
    console assets:install public
fi
console fos:js-routing:dump --format=js --target=public/js/fos_js_routes.js

# 7. Cron (experiment:execute jobs are managed from the /cron admin page).
# Cron jobs do not inherit the container environment, pam_env loads it from /etc/environment.
printenv | grep -E '^(APP_|DATABASE_|MAILER_|VAR_DUMPER_|PROJECT_)' > /etc/environment || true
cron

log "Ready"
exec "$@"

DAMIS-GUI
=========

Graphical user interface for Data Analysis and Mining Infrastructure for Scientists 

Running with Docker
=========

Services: `apache` (httpd 2.4, web server) → `php` (php-fpm 8.5, Symfony 7.4 LTS + Twig) → `mysql` (MySQL 8.4 LTS).

`docker compose up -d --build`, then open http://localhost:8080 (first start takes a few minutes, follow it with `docker compose logs -f php` until `[damis] Ready`).

On start the `php` container (`docker/php/entrypoint.sh`) does whatever is missing:
generates `config/services.yml` from `config/services.yml.dist`, links `web/` assets into `public/`,
runs `composer install` and the Encore build, upgrades the database schema, runs migrations,
dumps JS routes and starts cron (for `experiment:execute` jobs managed at `/cron`).

Database: on the first start (empty `mysql_data` volume) MySQL loads `sql/sql_structure.sql` + `sql/data.sql`.
To use real data instead, put a dump (`*.sql` or `*.sql.gz`) into `docker/mysql/import/` before the first start
(or re-seed with `docker compose down -v && docker compose up -d`). MySQL is exposed on `localhost:3307` (damis/damis).

Sample users have legacy password hashes, set a new password to log in:
`docker compose exec php bin/console fos:user:change-password admin <password>`

Ports, credentials and `APP_ENV` can be overridden via variables in a `.env` file (see `docker-compose.yml`).
Console: `docker compose exec php bin/console <command>`.

Tests
=========

Run in the running Docker environment:

- `docker compose exec -u www-data php vendor/bin/phpunit` - functional and unit tests (`tests/`) against the
  `damis_test` database, recreated on every run (`DAMIS_TEST_KEEP_SCHEMA=1` keeps it for single tests);
- `docker compose exec -u www-data -e SMOKE_USER=<admin> -e SMOKE_PASSWORD=<password> php bash tests/smoke/smoke.sh`:
  HTTP smoke test through Apache (all pages, login, dataset upload and conversions) as an administrator;
- `docker compose exec -u www-data php php tests/smoke/damis-service.php` - every DamisService operation over SOAP.

GitHub Actions (`.github/workflows/ci.yml`) run all three on every push and pull request with a fresh
`docker compose up --build`, together with InScience/DAMIS and InScience/DAMIS_WebService (the branch of the
same name when it exists, otherwise the default branch), plus `composer audit` and `npm audit` (also weekly).

Initial configuration
=========

Copy \config\services.yml.dist to \config\services.yml
Fill in services.yml with appopriate values.

For login to work:
project_domain (in services.yml) and local host name should be similar.
I.e. project_domain: .something and in hosts: 127.0.0.1 test.something

Test data
=========

Test data can be found in sql/data.sql file

Commands:
=========
To clear backend cache: \
`php bin/console cache:clear --env=prod &&  php bin/console cache:clear --env=dev` 

To build the entire project backend + frontend: \
`bash buildProject.sh`



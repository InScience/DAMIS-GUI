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

Deployment (damis.midas.lt)
=========

The server runs without Docker: Ubuntu 24.04, Apache 2.4 with mod_php 8.4, MySQL 8, OpenMPI. `deploy/deploy.sh`
deploys this repository with its sibling checkouts of InScience/DAMIS_WebService and InScience/DAMIS
(cloned next to it when missing):

    cd ~/DAMIS-GUI && git pull --ff-only    # only the first time, to get deploy/
    sudo deploy/deploy.sh                    # pulls origin/master of all three repositories, then deploys
    sudo SMOKE_USER=<admin> SMOKE_PASSWORD=<password> deploy/deploy.sh   # also runs tests/smoke/smoke.sh

Prerequisites: Node.js >= 22.18 (Ubuntu 24.04 ships 18, e.g. Node.js 22 LTS from NodeSource) and composer;
the build packages (gSOAP, OpenMPI, libcurl, AWStats) are installed by the script. It refuses to run while an
experiment is executing, and stops cron for the time of the deployment.

What it does:

- backs up the database, `/usr/lib/cgi-bin`, the Apache sites, `config/services.yml`, `.env.local` and the
  current git revisions into `/var/backups/damis/<timestamp>/`;
- GUI: `composer install` (without dev packages in prod), Encore build, migrations, assets, JS routes, cache;
  `.env.local` gets `DAMIS_SERVICE_WSDL`, `DAMIS_DATA_HOST`, `DAMIS_AWSTATS_DIR`; `public/` gets symlinks into `web/`
  (copies of older installations are moved to the backup); `var/` belongs to www-data, so run console commands as
  `sudo -u www-data php bin/console ...`;
- service: builds `Algorithms` (`/opt/damis/bin`) and `DamisService.cgi` (`/usr/lib/cgi-bin`), writes `settings.csv`,
  `/etc/damis/damis.wsdl` and `/etc/damis/logging.conf` (log: `/var/log/damis/damis-service.log`), result files in
  `/var/lib/damis/data`. It runs as CGI on `http://localhost:8080` only (`deploy/apache/damis-service.conf`) and
  replaces the old standalone `damis.service` (disabled). `serve-cgi-bin` is disabled, so `/cgi-bin/` is no longer
  public;
- Apache: both damis.midas.lt virtual hosts get `Include /etc/apache2/damis-gui-vhost.conf`
  (`deploy/apache/damis-gui-vhost.conf`: daily access logs for AWStats, no PHP from `uploads/`);
- AWStats: `/etc/awstats/awstats.damis.conf` and `/usr/local/bin/damis-awstats-update` from `docker/awstats/`, run
  hourly by `/etc/cron.d/damis-awstats` into `/var/www/awstats` (shown under `/admin/statistics`);
- ends with `tests/smoke/damis-service.php` (every service operation through SOAP).

Rollback (B = the backup directory):

    gunzip < $B/<database>.sql.gz | sudo mysql <database>
    sudo a2dissite damis-service && sudo a2enconf serve-cgi-bin     # frees port 8080
    sudo tar -C /etc/apache2 -xzf $B/apache2-sites.tar.gz && sudo systemctl reload apache2
    sudo tar -C /usr/lib -xzf $B/cgi-bin.tar.gz && sudo systemctl enable --now damis.service
    git checkout <revision from $B/revisions.txt>, restore .env.local, then bash buildProject.sh

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



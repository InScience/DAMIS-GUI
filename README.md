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

Production runs the same Docker stack with `docker-compose.prod.yml` on top: `APP_ENV=prod`, https on port 443
with the server's certificate (http redirects to it, except `/uploads/` and requests between the containers),
secrets and the mail server from `.env`, no Mailpit, rotated container logs. MySQL and DamisService are published
on localhost only. The server's `.env` (gitignored, mode 600, template `deploy/docker.env.example`) sets
`COMPOSE_FILE`, so `sudo docker compose ...` in `~/DAMIS-GUI` always uses both files. The sibling checkouts of
InScience/DAMIS_WebService and InScience/DAMIS next to `DAMIS-GUI` are built into the `damis-service` image.

Prerequisite: Docker Engine with the compose plugin, from Docker's repository:

    sudo install -m 0755 -d /etc/apt/keyrings
    sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
    echo "deb [signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo $VERSION_CODENAME) stable" \
      | sudo tee /etc/apt/sources.list.d/docker.list
    sudo apt-get update && sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

**Switch from the host installation (once):** `sudo deploy/switch-to-docker.sh`. While the site still runs, it backs up
the database, uploads, `services.yml` and the www-data crontab into `/var/backups/damis/<timestamp>-switch/`,
creates `.env` (random passwords, the certificate files of the Apache https site), builds the images, the PHP
dependencies and the frontend. Then, with a few minutes of downtime, it stops the host Apache, the www-data crontab
and MySQL, imports a fresh dump into the mysql container, rewrites the host paths of the cron jobs (`/cron` page),
starts the stack and checks https and DamisService. If anything fails after Apache was stopped, it starts the host
installation again by itself. The host services stay installed (disabled).

**Updates:** `sudo deploy/deploy.sh` pulls origin/master of all three repositories (missing ones are cloned), backs up
the database, uploads and configuration into `/var/backups/damis/<timestamp>/`, rebuilds the images, the PHP
dependencies (without dev packages) and the frontend, restarts the containers (the php container migrates the
database and rewrites the crontab on start) and ends with smoke tests (https, every DamisService operation over
SOAP). `--no-pull` deploys the checkouts as they are. It refuses to run while an experiment is executing. With an
administrator account it also runs `tests/smoke/smoke.sh`; the password is typed, never put on a command line:

    export SMOKE_USER=<admin>; read -rsp 'password: ' SMOKE_PASSWORD; export SMOKE_PASSWORD
    sudo --preserve-env=SMOKE_USER,SMOKE_PASSWORD deploy/deploy.sh

Mail: set `MAILER_DSN` in `.env` to the SMTP server given by the university IT (until then `null://null`, nothing is
sent, and the scripts warn), then `sudo docker compose up -d php`.

**Backups:** `deploy/backup.sh` (database dump, `public/uploads`, `services.yml`, `.env`, repository revisions) runs
daily at 03:15 from `/etc/cron.d/damis-backup` (installed by `deploy.sh`) into `/var/backups/damis/daily/<timestamp>/`,
keeps 30 days (`BACKUP_KEEP_DAYS`), log `/var/log/damis-backup.log`; a failed backup is removed, not kept half-done.
`deploy.sh` keeps its own backups in `/var/backups/damis/<timestamp>/` (remove old ones by hand). To copy them off the
server: `sudo rsync -a /var/backups/damis/ <elsewhere>`. Restore (B = a backup directory, in `~/DAMIS-GUI`):

    sudo docker compose stop php                     # no requests or cron jobs meanwhile
    gzip -dc $B/database.sql.gz | sudo docker compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql -uroot "$MYSQL_DATABASE"'
    sudo tar -C public -xzf $B/uploads.tar.gz        # only if the uploaded files are needed too
    sudo docker compose start php

Everyday commands, in `~/DAMIS-GUI`: `sudo docker compose ps`, `sudo docker compose logs -f php`,
`sudo docker compose exec -u www-data php bin/console <command>`.

Back to the host installation (B = the `-switch` backup directory; data changed since the switch is in the
container database, dump it first if it is needed):

    sudo docker compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqldump -uroot "$MYSQL_DATABASE"' > ~/docker-db.sql
    sudo docker compose down && sudo mv .env $B/env.docker && cp $B/env.local .env.local
    sudo chown -R www-data: var public/uploads
    sudo crontab -u www-data $B/crontab-www-data && sudo cp $B/cron.d-damis-awstats /etc/cron.d/damis-awstats
    sudo systemctl enable --now mysql apache2        # then, if wanted: sudo mysql <database> < ~/docker-db.sql

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



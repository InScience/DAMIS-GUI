FROM php:8.4-fpm-bookworm

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Node.js for the webpack encore build (Debian's nodejs 18 is too old for package-lock.json)
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# git/unzip: composer, cron: experiment execution jobs
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip cron \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions pdo_mysql intl zip soap gd bcmath opcache

# Cron in a container: pam_loginuid fails (jobs silently never run) and cron's PATH lacks /usr/local/bin
RUN sed -i '/pam_loginuid.so/s/^/#/' /etc/pam.d/cron \
    && ln -s /usr/local/bin/php /usr/bin/php

# Match www-data to the host user so files written to the bind mount keep sane ownership
ARG UID=1000
ARG GID=1000
RUN groupmod -o -g "${GID}" www-data && usermod -o -u "${UID}" -g www-data www-data

ENV COMPOSER_HOME=/tmp/composer \
    npm_config_cache=/tmp/npm

COPY conf.d/damis.ini "$PHP_INI_DIR/conf.d/zz-damis.ini"
COPY php-fpm.d/damis.conf /usr/local/etc/php-fpm.d/zzz-damis.conf
COPY entrypoint.sh /usr/local/bin/damis-entrypoint
RUN chmod +x /usr/local/bin/damis-entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["damis-entrypoint"]
CMD ["php-fpm"]

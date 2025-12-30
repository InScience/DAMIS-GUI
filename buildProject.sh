#!/usr/bin/env bash
# HELPER SCRIPT TO BUILD THE ENTIRE BACKEND + FRONTEND

php bin/console cache:clear --env=prod && php bin/console cache:clear --env=dev
php bin/console assets:install public
php bin/console assets:install --symlink
php bin/console assets:install
npm install
npm run build
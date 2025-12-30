DAMIS-GUI
=========

Graphical user interface for Data Analysis and Mining Infrastructure for Scientists 

Initial configuration
=========

Copy \app\config\parameters.yml.dist to \app\config\parameters.yml
Fill in parameters.yml with appopriate values.

For login to work:
project_domain (in parameters.yml) and local host name should be similar.
I.e. project_domain: .something and in hosts: 127.0.0.1 test.something

Test data
=========

Test data can be found in sql/data.sql file




Commands:


php bin/console cache:clear --env=prod &&  php bin/console cache:clear --env=dev
php bin/console assets:install public
php bin/console assets:install --symlink

php bin/console assets:install
npm run build

php bin/console cache:clear --env=prod; php bin/console cache:clear --env=dev; php bin/console assets:install public; php bin/console assets:install --symlink; php bin/console assets:install; npm run build
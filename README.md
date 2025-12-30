DAMIS-GUI
=========

Graphical user interface for Data Analysis and Mining Infrastructure for Scientists 

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



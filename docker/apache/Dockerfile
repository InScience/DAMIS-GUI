FROM httpd:2.4

# ssl + socache_shmcb: https in production (docker-compose.prod.yml starts httpd with -DDAMIS_SSL)
RUN sed -i \
        -e 's/^#\(LoadModule proxy_module \)/\1/' \
        -e 's/^#\(LoadModule proxy_fcgi_module \)/\1/' \
        -e 's/^#\(LoadModule rewrite_module \)/\1/' \
        -e 's/^#\(LoadModule ssl_module \)/\1/' \
        -e 's/^#\(LoadModule socache_shmcb_module \)/\1/' \
        /usr/local/apache2/conf/httpd.conf \
    && echo 'Include conf/extra/damis.conf' >> /usr/local/apache2/conf/httpd.conf

# public host name (production: damis.midas.lt)
ENV DAMIS_SERVER_NAME=localhost

COPY damis.conf damis-app.conf /usr/local/apache2/conf/extra/

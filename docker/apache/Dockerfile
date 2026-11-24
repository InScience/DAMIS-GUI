FROM httpd:2.4

RUN sed -i \
        -e 's/^#\(LoadModule proxy_module \)/\1/' \
        -e 's/^#\(LoadModule proxy_fcgi_module \)/\1/' \
        -e 's/^#\(LoadModule rewrite_module \)/\1/' \
        /usr/local/apache2/conf/httpd.conf \
    && echo 'Include conf/extra/damis.conf' >> /usr/local/apache2/conf/httpd.conf

COPY damis.conf /usr/local/apache2/conf/extra/damis.conf

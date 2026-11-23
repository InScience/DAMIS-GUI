# AWStats for the DAMIS GUI: reads the apache access logs every hour and builds static pages
# (Lithuanian and English), which the application shows to administrators (/admin/statistics).
FROM ubuntu:26.04

ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update \
    && apt-get install -y --no-install-recommends awstats tzdata \
    && rm -rf /var/lib/apt/lists/* /etc/cron.d/awstats

COPY awstats.damis.conf.template /etc/awstats/awstats.damis.conf.template
COPY --chmod=0755 damis-awstats-update /usr/local/bin/damis-awstats-update
COPY --chmod=0755 entrypoint.sh /usr/local/bin/damis-awstats-entrypoint

ENV TZ=Europe/Vilnius \
    AWSTATS_SITE_DOMAIN=damis.midas.lt \
    LOG_RETENTION_DAYS=90

VOLUME ["/var/lib/awstats", "/var/www/awstats"]

ENTRYPOINT ["damis-awstats-entrypoint"]

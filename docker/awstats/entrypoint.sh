#!/bin/bash
# Renders the AWStats configuration and runs the update every hour (5 minutes past the hour).
#   AWSTATS_SITE_DOMAIN  public host name of the GUI (default damis.midas.lt)
#   LOG_RETENTION_DAYS   access log files are deleted after this many days (default 90)
# "docker compose exec awstats damis-awstats-update" runs an update immediately.
set -euo pipefail

sed "s#__SITE_DOMAIN__#${AWSTATS_SITE_DOMAIN:-damis.midas.lt}#g" \
    /etc/awstats/awstats.damis.conf.template > /etc/awstats/awstats.damis.conf
mkdir -p /var/lib/awstats /var/www/awstats /var/log/damis-apache

echo "awstats: site ${AWSTATS_SITE_DOMAIN:-damis.midas.lt}, logs kept ${LOG_RETENTION_DAYS:-90} days, timezone ${TZ:-UTC}"

# bash as PID 1 does not stop on SIGTERM by itself; sleep in the background so the trap runs immediately
trap 'exit 0' TERM INT

while true; do
    damis-awstats-update || echo "$(date '+%F %T') update failed"
    sleep $(( 3600 - $(date +%s) % 3600 + 300 )) &
    wait $!
done

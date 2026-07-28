#!/bin/sh
set -e

# Known Railway platform bug (since ~Dec 2025): containers can boot with more than one Apache MPM
# module enabled even though the php:*-apache base image only ships mpm_prefork — Apache then
# refuses to start with "AH00534: More than one MPM loaded". Force prefork-only, every boot.
# https://station.railway.com/questions/ah00534-apache2-configuration-error-m-9f7a13b2
a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* 2>/dev/null || true
a2enmod mpm_prefork >/dev/null 2>&1 || true

# Railway assigns $PORT at runtime (not build time) — rewrite Apache's listen port here, every boot.
# Anchored patterns (^...$ / trailing >) so this stays idempotent if the entrypoint reruns on a
# reused filesystem (e.g. `railway restart`, which does not recreate the container from the image) —
# an unanchored "Listen 80" would keep matching inside its own previous output ("Listen 8080" etc).
PORT="${PORT:-80}"
sed -i -E "s/^Listen [0-9]+$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i -E "s/:[0-9]+>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

echo "Running database migrations..."
php spark migrate --all || echo "Migration step failed or nothing to migrate — continuing startup."

exec "$@"

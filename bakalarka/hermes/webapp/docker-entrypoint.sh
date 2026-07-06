#!/bin/bash
# docker-entrypoint.sh – Apache + jednorazové PHP joby pri štarte (pozri docker-startup-jobs.sh); plán v reminder kontajneri.
set -e

HERMES_BASE_CONFIG="${HERMES_BASE_CONFIG:-/etc/hermes/config.json}"
HERMES_CONFIG="${HERMES_CONFIG:-/tmp/hermes_runtime_config.json}"
export HERMES_BASE_CONFIG HERMES_CONFIG

php /usr/local/lib/hermes/merge-runtime-config.php

mkdir -p /tmp/hermes_invoices /tmp/hermes_pdf_cache /var/log/hermes
chown www-data:www-data /tmp/hermes_invoices /tmp/hermes_pdf_cache /var/log/hermes
chmod 755 /tmp/hermes_invoices /tmp/hermes_pdf_cache /var/log/hermes

# Rovnaké úlohy ako cron (overdue / šablóny / bank sync) hneď pri štarte – bez čakania na 7:55 / 8:00.
# Ak MariaDB ešte nie je pripravená, skript len zaloguje chybu; Apache štartuje aj tak.
/var/www/html/docker-startup-jobs.sh

echo "[hermes-webapp] Starting Apache..."
exec apache2-foreground

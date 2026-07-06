#!/bin/bash
# Hermes reminder container: merged runtime config + cron (no Apache).
set -euo pipefail

echo "[hermes-reminder] Hermes background jobs – $(date -u '+%Y-%m-%d %H:%M:%S UTC')"

[[ -f /etc/hermes/config.json ]] || {
    echo "[hermes-reminder] FATAL: /etc/hermes/config.json not found." >&2
    exit 1
}

HERMES_BASE_CONFIG="${HERMES_BASE_CONFIG:-/etc/hermes/config.json}"
HERMES_CONFIG="${HERMES_CONFIG:-/tmp/hermes_runtime_config.json}"
export HERMES_BASE_CONFIG HERMES_CONFIG

php /usr/local/lib/hermes/merge-runtime-config.php

umask 077
{
    for var in DB_HOST DB_PORT DB_NAME DB_USER DB_PASSWORD \
        SMTP_HOST SMTP_PORT SMTP_USER SMTP_PASSWORD SMTP_FROM SMTP_FROM_NAME SMTP_TLS \
        HERMES_CONFIG HERMES_BASE_CONFIG \
        HERMES_PUBLIC_URL HERMES_BANK_TOKEN_KEY HERMES_SBAS_CONFIG SBAS_CLIENT_SECRET SBAS_USE_MOCK; do
        eval "val=\${$var-}"
        printf '%s=%q\n' "$var" "$val"
    done
} >/etc/hermes/cron.env
chmod 600 /etc/hermes/cron.env

mkdir -p /var/log/hermes /tmp/hermes_invoices /tmp/hermes_pdf_cache
chmod 755 /var/log/hermes /tmp/hermes_invoices /tmp/hermes_pdf_cache

if [[ "${RUN_ON_START:-true}" == "true" ]]; then
    echo "[hermes-reminder] RUN_ON_START=true – running billing_reminder once..."
    /usr/local/bin/hermes-run-billing-reminder.sh || true
fi

echo "[hermes-reminder] Starting cron..."
exec cron -f -L /var/log/hermes/cron_daemon.log

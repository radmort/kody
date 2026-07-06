#!/bin/bash
# Load env written by reminder entrypoint so cron jobs see DB_* / SMTP_* / HERMES_*.
set -euo pipefail
if [[ -f /etc/hermes/cron.env ]]; then
    set -a
    # shellcheck disable=SC1091
    source /etc/hermes/cron.env
    set +a
fi
exec "$@"

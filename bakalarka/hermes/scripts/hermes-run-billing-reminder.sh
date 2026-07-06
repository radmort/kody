#!/bin/bash
set -euo pipefail
if [[ -f /etc/hermes/cron.env ]]; then
    set -a
    # shellcheck disable=SC1091
    source /etc/hermes/cron.env
    set +a
fi
exec /usr/local/bin/billing_reminder "${HERMES_CONFIG:-/tmp/hermes_runtime_config.json}"

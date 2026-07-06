#!/bin/bash
# Jednorazovo po štarte kontajnera: rovnaké PHP joby ako v cron (bez čakania na plán).
# Log: /var/log/hermes/startup_jobs (pri volume ./logs aj na hostiteľovi).
set +e
LOG="${HERMES_STARTUP_JOBS_LOG:-/var/log/hermes/startup_jobs}"
mkdir -p "$(dirname "$LOG")"

stamp() { date '+%Y-%m-%d %H:%M:%S %z'; }

# Krátke čakanie na TCP port DB (depends_on nečaká na „pripravená na spojenia“).
wait_db_port() {
    local host="${DB_HOST:-mariadb}"
    local port="${DB_PORT:-3306}"
    local i=0
    while [[ $i -lt 30 ]]; do
        if (echo >/dev/tcp/"$host"/"$port") >/dev/null 2>&1; then
            echo "[$(stamp)] startup: DB ${host}:${port} reachable" >>"$LOG"
            return 0
        fi
        i=$((i + 1))
        sleep 2
    done
    echo "[$(stamp)] startup: DB ${host}:${port} wait timeout, running jobs anyway" >>"$LOG"
}

wait_db_port

run_job() {
    local name="$1"
    local script="/var/www/html/cron/${name}.php"
    echo "=== [$(stamp)] startup: ${name}.php ===" >>"$LOG"
    php "$script" >>"$LOG" 2>&1
    local ec=$?
    if [[ $ec -ne 0 ]]; then
        echo "[$(stamp)] startup: ${name}.php exit ${ec}" >>"$LOG"
    fi
}

echo "[hermes-webapp] Running startup jobs (see ${LOG})..."
run_job overdue
run_job monthly
run_job bank_sync
echo "[$(stamp)] startup jobs batch done" >>"$LOG"
exit 0

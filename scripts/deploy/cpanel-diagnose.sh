#!/usr/bin/env bash
# Requests one address on the server and shows the newest errors of the application log,
# shortened: the message and the application's own lines of the stack, no request data.
set -Eeuo pipefail
APP="$HOME/sarafbook"
URL="${CHECK_URL:-}"
STAMP=$(date '+%Y-%m-%d %H:%M:%S')
for u in $URL; do
    code=$(curl -s -o /tmp/sb-check.html -w '%{http_code} %{size_download}b -> %{redirect_url}' -m 25 "$u" || true)
    title=$(grep -o '<title>[^<]*' /tmp/sb-check.html 2>/dev/null | head -1 | sed 's/<title>//' || true)
    echo "GET $u: $code  ${title:0:80}"
done
rm -f /tmp/sb-check.html
echo "(errors logged before $STAMP are older than these requests)"
if [ -n "${SMOKE_TENANT:-}" ]; then
    PHP=""
    for candidate in /opt/cpanel/ea-php84/root/usr/bin/php /opt/alt/php84/usr/bin/php /opt/cpanel/ea-php85/root/usr/bin/php "$(command -v php || true)"; do
        [ -n "$candidate" ] && [ -x "$candidate" ] && PHP="$candidate" && break
    done
    echo "== pages of $SMOKE_TENANT opened as its administrator"
    (cd "$APP" && "$PHP" artisan tenants:smoke "$SMOKE_TENANT" ${SMOKE_PATHS:-} --no-interaction 2>&1 | sed -E "s#$HOME/##g" | cut -c1-420) || true
fi
LOG=$(ls -t "$APP"/storage/logs/*.log 2>/dev/null | head -1)
[ -n "$LOG" ] || { echo "No log file yet."; exit 0; }
echo "== newest errors"
grep -nE '^\[[0-9]{4}-[0-9]{2}-[0-9]{2}' "$LOG" | tail -"${ERRORS:-3}" | while IFS=: read -r line rest; do
    echo "--- ${rest:0:700}"
    sed -n "$((line+1)),$((line+60))p" "$LOG" | grep -E '^#[0-9]+ ' | grep -E '/sarafbook/(app|routes|resources|bootstrap|database|storage/framework/views)/' | head -8 | sed -E "s#$HOME/##; s/\(.*//" || true
done

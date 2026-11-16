#!/usr/bin/env bash
# Requests one address on the server and shows the newest errors of the application log,
# shortened: the message and the application's own lines of the stack, no request data.
set -Eeuo pipefail
APP="$HOME/sarafbook"
URL="${CHECK_URL:-}"
if [ -n "$URL" ]; then
    code=$(curl -s -o /dev/null -w '%{http_code} -> %{redirect_url}' -m 25 "$URL" || true)
    echo "GET $URL: $code"
fi
LOG=$(ls -t "$APP"/storage/logs/*.log 2>/dev/null | head -1)
[ -n "$LOG" ] || { echo "No log file yet."; exit 0; }
echo "== newest errors"
grep -nE '^\[[0-9]{4}-[0-9]{2}-[0-9]{2}' "$LOG" | tail -"${ERRORS:-3}" | while IFS=: read -r line rest; do
    echo "--- ${rest:0:700}"
    sed -n "$((line+1)),$((line+60))p" "$LOG" | grep -E '^#[0-9]+ ' | grep -E '/sarafbook/(app|routes|resources|bootstrap|database|storage/framework/views)/' | head -8 | sed -E "s#$HOME/##; s/\(.*//" || true
done

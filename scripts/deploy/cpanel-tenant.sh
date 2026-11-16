#!/usr/bin/env bash
# Adds one Sarafi in a cPanel account: subdomain, database, tables, starting data, administrator.
# Run on the server by the "New Sarafi" workflow. Settings arrive as environment variables:
#   APP_DOMAIN, TENANT_NAME (a-z 0-9 -), TENANT_TITLE, TENANT_STATUS, TENANT_EXPIRES, SARAFI_ADMIN_PASSWORD
set -Eeuo pipefail
export PATH="$PATH:/usr/local/cpanel/bin:/usr/local/bin:/usr/bin"

CP=$(whoami)
APP="$HOME/sarafbook"
DOMAIN="${APP_DOMAIN:-sarafbook.com}"
NAME="${TENANT_NAME:?}"
[[ "$NAME" =~ ^[a-z0-9]([a-z0-9-]{0,38}[a-z0-9])?$ ]] || { echo "The short name may hold only a-z, 0-9 and '-'."; exit 1; }
: "${SARAFI_ADMIN_PASSWORD:?The secret SARAFI_ADMIN_PASSWORD is needed}"

PHP=""
for candidate in /opt/cpanel/ea-php84/root/usr/bin/php /opt/alt/php84/usr/bin/php /opt/cpanel/ea-php85/root/usr/bin/php /opt/alt/php85/usr/bin/php "$(command -v php || true)"; do
    [ -n "$candidate" ] && [ -x "$candidate" ] && PHP="$candidate" && break
done
DB="${CP}_sb_${NAME//-/_}"

echo "== Subdomain $NAME.$DOMAIN"
uapi SubDomain addsubdomain domain="$NAME" rootdomain="$DOMAIN" dir=public_html | grep -E "status|errors|reason" | head -4 || true
echo "== Database"
uapi Mysql create_database name="$DB" | grep -E "status|errors" | head -3 || true
uapi Mysql set_privileges_on_database user="${CP}_sbook" database="$DB" privileges='ALL PRIVILEGES' | grep -E "status|errors" | head -3 || true

echo "== Sarafi"
cd "$APP"
args=("$NAME" "--name=${TENANT_TITLE:-$NAME}" "--status=${TENANT_STATUS:-trial}" "--existing-database" "--admin-password=$SARAFI_ADMIN_PASSWORD" "--no-interaction")
[ -n "${TENANT_EXPIRES:-}" ] && args+=("--expires=$TENANT_EXPIRES")
# the last table of the command shows the password: keep only the lines above it
"$PHP" artisan tenants:create "${args[@]}" 2>&1 | grep -vE "^\s*[|+]" | sed "s/${SARAFI_ADMIN_PASSWORD//\//\\/}/(hidden)/g"
if [ "${DEMO_DATA:-0}" = 1 ]; then
    echo "== Sample data"
    "$PHP" artisan tenants:run "db:seed --class=DemoSeeder --force" --tenant="$NAME" --no-interaction 2>&1 | tail -25
fi
"$PHP" artisan tenants:list --no-interaction 2>&1 | grep -E "Short name| $NAME " || true
code=$(curl -s -o /dev/null -w '%{http_code}' -m 20 "https://$NAME.$DOMAIN/login" || true)
echo "https://$NAME.$DOMAIN/login answered: $code"
echo "Address: https://$NAME.$DOMAIN   username: admin   password: the secret SARAFI_ADMIN_PASSWORD"
echo "Run AutoSSL in cPanel so the new address gets its certificate."

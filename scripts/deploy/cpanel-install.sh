#!/usr/bin/env bash
# Installs or updates SarafBook in a cPanel account. Run on the server by the "Deploy" workflow:
# the settings arrive as environment variables on standard input, never on a command line.
#
#   APP_DOMAIN               sarafbook.com
#   DB_PASSWORD              password of the database user (used on the first install)
#   PLATFORM_ADMIN_EMAIL     first platform administrator (first install, or RESET_ADMIN=1)
#   PLATFORM_ADMIN_PASSWORD
set -Eeuo pipefail
export PATH="$PATH:/usr/local/cpanel/bin:/usr/local/bin:/usr/bin"

CP=$(whoami)
APP="$HOME/sarafbook"
DOMAIN="${APP_DOMAIN:-sarafbook.com}"
step() { printf '\n== %s\n' "$1"; }

step "PHP"
PHP=""
for candidate in /opt/cpanel/ea-php84/root/usr/bin/php /opt/alt/php84/usr/bin/php /opt/cpanel/ea-php85/root/usr/bin/php /opt/alt/php85/usr/bin/php; do
    [ -x "$candidate" ] && PHP="$candidate" && break
done
if [ -z "$PHP" ] && command -v php >/dev/null && php -r 'exit(PHP_VERSION_ID >= 80400 ? 0 : 1);'; then PHP=$(command -v php); fi
if [ -z "$PHP" ]; then
    echo "PHP 8.4 was not found on this server. Versions installed:"
    ls -d /opt/cpanel/ea-php*/ /opt/alt/php*/ 2>/dev/null || true
    exit 1
fi
"$PHP" -v | head -1
missing=""
for ext in pdo_mysql mbstring intl gd zip xml curl fileinfo; do "$PHP" -m | grep -qix "$ext" || missing="$missing $ext"; done
[ -n "$missing" ] && echo "Warning: PHP extensions not loaded:$missing"

FIRST=0
[ -f "$APP/.env" ] || FIRST=1

if [ "$FIRST" = 1 ]; then
    step "Databases (cPanel)"
    : "${DB_PASSWORD:?The secret DB_PASSWORD is needed for the first install}"
    uapi Mysql create_database name="${CP}_central" | grep -E "status|errors" | head -3 || true
    uapi Mysql create_user name="${CP}_sbook" password="$DB_PASSWORD" | grep -E "status|errors" | head -3 || true
    uapi Mysql set_privileges_on_database user="${CP}_sbook" database="${CP}_central" privileges='ALL PRIVILEGES' | grep -E "status|errors" | head -3 || true
fi

step "Code"
if [ -d "$APP/.git" ]; then
    git -C "$APP" fetch --quiet origin main
    git -C "$APP" reset --quiet --hard origin/main
else
    git clone --quiet --branch main https://github.com/fidatech/sarafbook.git "$APP"
fi
cd "$APP"
git log --oneline -1

step "Packages"
# cPanel often switches allow_url_fopen off for the command line; Composer needs it
[ -f composer.phar ] || curl -fsS https://getcomposer.org/installer | "$PHP" -d allow_url_fopen=On -- --quiet
"$PHP" -d allow_url_fopen=On -d memory_limit=-1 composer.phar install --no-dev --optimize-autoloader --no-interaction --no-progress 2>&1 | tail -8
[ -f vendor/autoload.php ] || { echo "The packages were not installed."; exit 1; }

if [ "$FIRST" = 1 ]; then
    step "Settings"
    cp .env.example .env
    chmod 600 .env
    setenv() { "$PHP" -r '[$f,$k,$v]=[$argv[1],$argv[2],$argv[3]]; $s=file_get_contents($f); $line=$k."=".(preg_match("/[\s#\"$]/",$v)?"\"".addcslashes($v,"\"\\$")."\"":$v); $s=preg_match("/^".preg_quote($k,"/")."=.*$/m",$s)?preg_replace("/^".preg_quote($k,"/")."=.*$/m",addcslashes($line,"\\$"),$s):$s."\n".$line."\n"; file_put_contents($f,$s);' .env "$1" "$2"; }
    setenv APP_ENV production
    setenv APP_DEBUG false
    setenv APP_URL "https://$DOMAIN"
    setenv CENTRAL_DOMAINS "$DOMAIN,www.$DOMAIN"
    setenv TENANT_BASE_DOMAIN "$DOMAIN"
    setenv TENANT_DB_PREFIX "${CP}_sb_"
    setenv DB_USERNAME "${CP}_sbook"
    setenv DB_PASSWORD "$DB_PASSWORD"
    setenv CENTRAL_DB_DATABASE "${CP}_central"
    setenv QUEUE_CONNECTION sync
    setenv LOG_LEVEL warning
    "$PHP" artisan key:generate --force --no-interaction | tail -1
fi

step "Database tables"
"$PHP" artisan config:clear --no-interaction >/dev/null
"$PHP" artisan migrate --force --no-interaction 2>&1 | tail -6
"$PHP" artisan tenants:migrate --no-interaction 2>&1 | tail -20 || echo "Some Sarafi databases could not be updated (see above)."

if [ "$FIRST" = 1 ] || [ "${RESET_ADMIN:-0}" = 1 ]; then
    step "Platform administrator"
    : "${PLATFORM_ADMIN_EMAIL:?The secret PLATFORM_ADMIN_EMAIL is needed}"
    : "${PLATFORM_ADMIN_PASSWORD:?The secret PLATFORM_ADMIN_PASSWORD is needed}"
    "$PHP" artisan platform:admin "$PLATFORM_ADMIN_EMAIL" --password="$PLATFORM_ADMIN_PASSWORD" --no-interaction | head -1 | sed -E 's/[^ ]+@[^ ]+/(email hidden)/'
fi

step "Caches"
"$PHP" artisan optimize --no-interaction 2>&1 | tail -6

step "Web folder"
if [ -L "$HOME/public_html" ]; then
    echo "public_html already points at $(readlink "$HOME/public_html")"
else
    [ -e "$HOME/public_html" ] && mv "$HOME/public_html" "$HOME/public_html_before_sarafbook_$(date +%Y%m%d%H%M%S)"
    ln -s "$APP/public" "$HOME/public_html"
    echo "public_html now points at $APP/public (the earlier folder was kept beside it)"
fi

step "Check"
"$PHP" artisan about --only=environment 2>/dev/null | grep -E "Laravel Version|PHP Version|Environment|Debug Mode" || true
code=$(curl -s -o /dev/null -w '%{http_code}' -m 20 "https://$DOMAIN/platform/login" || true)
echo "https://$DOMAIN/platform/login answered: $code"

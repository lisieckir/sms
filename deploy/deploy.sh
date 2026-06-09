#!/bin/sh
set -e

DIR="$(cd "$(dirname "$0")" && pwd)"
INVENTORY="${1:-$DIR/inventory}"

if [ -f "$INVENTORY" ]; then
    . "$INVENTORY"
fi

REMOTE_HOST="${REMOTE_HOST:?inventory: REMOTE_HOST not set}"
REMOTE_USER="${REMOTE_USER:-root}"
REMOTE_PATH="${REMOTE_PATH:-/var/www/sms}"
SSH_KEY="${SSH_KEY:-}"
APP_SECRET="${APP_SECRET:?inventory: APP_SECRET not set}"
DEFAULT_URI="${DEFAULT_URI:-}"
MAILER_DSN="${MAILER_DSN:-}"
PHP_VERSION="${PHP_VERSION:-83}"

SSH_OPTS="${SSH_KEY:+-i $SSH_KEY} -o StrictHostKeyChecking=accept-new"

info()  { printf "\033[1;34m==>\033[0m %s\n" "$*"; }
ok()    { printf "\033[1;32m OK\033[0m  %s\n" "$*"; }
die()   { printf "\033[1;31mERR\033[0m  %s\n" "$*"; exit 1; }
remote() { ssh $SSH_OPTS "$REMOTE_USER@$REMOTE_HOST" "$@"; }

if [ "$APP_SECRET" = "change_this_to_random_string" ]; then
    die "Edit APP_SECRET in your inventory file - do not use the example value."
fi

info "Deploying to $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH"

info "Ensuring remote directory exists"
remote "mkdir -p $REMOTE_PATH"

info "Syncing application code"
rsync -az --delete \
    -e "ssh $SSH_OPTS" \
    --filter=':- .gitignore' \
    --exclude=.git/ \
    --exclude=docker/ \
    --exclude=docs/ \
    --exclude=Makefile \
    --exclude=docker-compose.yml \
    --exclude='*.local' \
    --exclude=app/var/ \
    --exclude=app/vendor/ \
    --exclude=app/node_modules/ \
    "$DIR/../" \
    "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/"
ok "Files synced"

info "Installing PHP dependencies"
remote <<SCRIPT
cd $REMOTE_PATH/app
composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | sed 's/^/  composer: /'
SCRIPT
ok "Dependencies installed"

info "Configuring production environment"
remote <<SCRIPT
cd $REMOTE_PATH/app
if [ ! -f .env ]; then
    export APP_SECRET='$APP_SECRET'
    export DEFAULT_URI='$DEFAULT_URI'
    export MAILER_DSN='$MAILER_DSN'
    envsubst '\$APP_SECRET,\$DEFAULT_URI,\$MAILER_DSN' \
        < $REMOTE_PATH/deploy/templates/sms.env > .env
    ok ".env created"
else
    ok ".env already exists, skipping"
fi
SCRIPT

info "Ensuring PocketBase is running"
remote "rc-service pocketbase status 2>&1 >/dev/null || rc-service pocketbase start" 2>&1 | sed 's/^/  pocketbase: /'

info "Configuring nginx"
remote <<SCRIPT
cp $REMOTE_PATH/deploy/templates/nginx.sms.conf /etc/nginx/http.d/sms.conf
rc-service nginx reload 2>/dev/null || rc-service nginx start 2>&1 | sed 's/^/  nginx: /'
ok "nginx configured"
SCRIPT

info "Setting permissions"
remote <<SCRIPT
cd $REMOTE_PATH/app
chown -R :nginx var public
chmod -R 775 var/cache var/log
ok "permissions set"
SCRIPT

info "Clearing and warming cache"
remote <<SCRIPT
cd $REMOTE_PATH/app
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear --no-warmup 2>&1 | sed 's/^/  console: /'
APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup 2>&1 | sed 's/^/  console: /'
ok "cache warmed"
SCRIPT

info "Restarting PHP-FPM"
remote "rc-service php${PHP_VERSION}-fpm restart" 2>&1 | sed 's/^/  php-fpm: /'

info "Seeding initial data (safe to re-run)"
remote <<SCRIPT
cd $REMOTE_PATH/app
php bin/console app:seed-default-workflow 2>&1 | sed 's/^/  seed: /'
php bin/console app:fixtures:load 2>&1 | sed 's/^/  fixtures: /'
SCRIPT

ok "Deployment to $REMOTE_HOST complete!"
echo ""
echo "  Site:    $DEFAULT_URI"
echo "  SSH:     ssh $REMOTE_USER@$REMOTE_HOST"
echo "  App dir: $REMOTE_PATH/app"

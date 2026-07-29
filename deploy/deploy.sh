#!/bin/sh
set -e

DIR="$(cd "$(dirname "$0")" && pwd)"
SEED=false
INVENTORY="$DIR/inventory"

for arg in "$@"; do
    case "$arg" in
        --seed) SEED=true ;;
        --help) echo "Usage: $0 [inventory] [--seed]"; exit 0 ;;
        *) INVENTORY="$arg" ;;
    esac
done

if [ -f "$INVENTORY" ]; then
    . "$INVENTORY"
fi

REMOTE_HOST="${REMOTE_HOST:?inventory: REMOTE_HOST not set}"
REMOTE_USER="${REMOTE_USER:-root}"
REMOTE_PATH="${REMOTE_PATH:-/var/www/sms}"
SSH_KEY="${SSH_KEY:-}"
SSH_PORT="${SSH_PORT:-22}"
APP_SECRET="${APP_SECRET:?inventory: APP_SECRET not set}"
DEFAULT_URI="${DEFAULT_URI:-}"
MAILER_DSN="${MAILER_DSN:-}"
PHP_BINARY="${PHP_BINARY:-php83}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php83-fpm}"

SSH_OPTS="${SSH_KEY:+-i $SSH_KEY} -p $SSH_PORT -o StrictHostKeyChecking=accept-new"

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

info "Ensuring Composer is available"
remote "command -v composer >/dev/null 2>&1 || (wget -q https://getcomposer.org/installer -O /tmp/composer-setup.php && $PHP_BINARY /tmp/composer-setup.php --install-dir=/usr/bin --filename=composer --quiet && rm /tmp/composer-setup.php)" 2>&1 | sed 's/^/  composer: /'

info "Installing PHP dependencies"
remote "cd $REMOTE_PATH/app && composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | sed 's/^/  composer: /'"
ok "Dependencies installed"

info "Configuring production environment"
remote "cd $REMOTE_PATH/app && if [ ! -f .env ]; then export APP_SECRET='$APP_SECRET' DEFAULT_URI='$DEFAULT_URI' MAILER_DSN='$MAILER_DSN' && envsubst '\$APP_SECRET,\$DEFAULT_URI,\$MAILER_DSN' < $REMOTE_PATH/deploy/templates/sms.env > .env && echo '  .env created'; else echo '  .env already exists'; fi"
ok "Environment configured"

info "Ensuring PocketBase is running"
remote "rc-service pocketbase status >/dev/null 2>&1 || rc-service pocketbase start" 2>&1 | sed 's/^/  pocketbase: /'

info "Configuring nginx"
remote "cp $REMOTE_PATH/deploy/templates/nginx.sms.conf /etc/nginx/http.d/sms.conf && ( rc-service nginx reload 2>&1 || rc-service nginx start 2>&1 )" | sed 's/^/  nginx: /'
ok "nginx configured"

info "Setting permissions"
remote "cd $REMOTE_PATH/app && mkdir -p var/cache var/log && touch var/log/prod.log var/log/deprecation.log && chown -R :nginx var public var/log && chmod -R 775 var/cache var/log"
ok "Permissions set"

info "Configuring logrotate"
remote "cp $REMOTE_PATH/deploy/templates/logrotate.sms /etc/logrotate.d/sms"
ok "Logrotate configured"

info "Clearing and warming cache"
remote "cd $REMOTE_PATH/app && APP_ENV=prod APP_DEBUG=0 $PHP_BINARY bin/console cache:clear --no-warmup 2>&1 | sed 's/^/  console: /' && APP_ENV=prod APP_DEBUG=0 $PHP_BINARY bin/console cache:warmup 2>&1 | sed 's/^/  console: /'"
ok "Cache warmed"

info "Running PocketBase schema migrations"
remote "cd $REMOTE_PATH/app && POCKETBASE_ADMIN_EMAIL='${POCKETBASE_ADMIN_EMAIL}' POCKETBASE_ADMIN_PASSWORD='${POCKETBASE_ADMIN_PASSWORD}' $PHP_BINARY bin/console app:pocketbase:migrate --no-interaction 2>&1 | sed 's/^/  migrate: /'"
ok "Migrations complete"

info "Restarting PHP-FPM"
remote "rc-service $PHP_FPM_SERVICE restart 2>&1 || rc-service php-fpm restart 2>&1 || echo '  WARNING: could not restart PHP-FPM (tried: $PHP_FPM_SERVICE, php-fpm)'" | sed 's/^/  php-fpm: /'

if [ "$SEED" = true ]; then
    info "Seeding initial data"
    remote "cd $REMOTE_PATH/app && $PHP_BINARY bin/console app:seed-default-workflow 2>&1 | sed 's/^/  seed: /' && $PHP_BINARY bin/console app:fixtures:load 2>&1 | sed 's/^/  fixtures: /'"
    ok "Seed data loaded"
fi

ok "Deployment to $REMOTE_HOST complete!"
echo ""
echo "  Site:    $DEFAULT_URI"
echo "  SSH:     ssh $REMOTE_USER@$REMOTE_HOST -p $SSH_PORT"
echo "  App dir: $REMOTE_PATH/app"
echo ""
echo "  Flags:   --seed   (re-seed fixtures)"

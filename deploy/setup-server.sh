#!/bin/sh
# First-time server bootstrap for Alpine Linux
# Run this ONCE after provisioning a fresh Alpine box.
set -e

REMOTE_HOST="${REMOTE_HOST:?usage: REMOTE_HOST=sms.mikr.us ./setup-server.sh}"
REMOTE_USER="${REMOTE_USER:-root}"
SSH_PORT="${SSH_PORT:-22}"
SSH_KEY="${SSH_KEY:-}"
PHP_VERSION="${PHP_VERSION:-83}"

SSH_CMD="ssh ${SSH_KEY:+-i $SSH_KEY} -p $SSH_PORT -o StrictHostKeyChecking=accept-new"

info() { printf "\033[1;34m==>\033[0m %s\n" "$*"; }
ok()   { printf "\033[1;32m OK\033[0m  %s\n" "$*"; }

remote() { $SSH_CMD "$REMOTE_USER@$REMOTE_HOST" "$@"; }

info "Updating system packages"
remote <<SCRIPT
apk update && apk upgrade
SCRIPT

info "Installing required packages"
remote <<SCRIPT
apk add \
    nginx \
    php${PHP_VERSION} \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-ctype \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-dom \
    php${PHP_VERSION}-iconv \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-opcache \
    php${PHP_VERSION}-openssl \
    php${PHP_VERSION}-phar \
    php${PHP_VERSION}-session \
    php${PHP_VERSION}-simplexml \
    php${PHP_VERSION}-tokenizer \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-xmlreader \
    php${PHP_VERSION}-xmlwriter \
    php${PHP_VERSION}-zip \
    git \
    curl \
    unzip \
    jq \
    rsync \
    acme.sh
SCRIPT

info "Creating application directories"
remote "mkdir -p /var/www/sms /var/log/nginx /etc/nginx/http.d"

info "Configuring PHP-FPM"
remote <<SCRIPT
sed -i 's/^listen = 127.0.0.1:9000/listen = \/run\/php-fpm.sock/' /etc/php${PHP_VERSION}/php-fpm.d/www.conf
sed -i 's/^;listen.owner = nobody/listen.owner = nginx/' /etc/php${PHP_VERSION}/php-fpm.d/www.conf
sed -i 's/^;listen.group = nobody/listen.group = nginx/' /etc/php${PHP_VERSION}/php-fpm.d/www.conf
sed -i 's/^;listen.mode = 0660/listen.mode = 0660/' /etc/php${PHP_VERSION}/php-fpm.d/www.conf
sed -i 's/^user = nobody/user = nginx/' /etc/php${PHP_VERSION}/php-fpm.d/www.conf
sed -i 's/^group = nobody/group = nginx/' /etc/php${PHP_VERSION}/php-fpm.d/www.conf

# Production PHP settings
cat > /etc/php${PHP_VERSION}/conf.d/99-sms.ini <<'PHPINI'
date.timezone = Europe/Warsaw
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 20M
post_max_size = 20M
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 0
realpath_cache_size = 4096K
PHPINI
SCRIPT

info "Installing Composer"
remote <<SCRIPT
if [ ! -x /usr/bin/composer ]; then
    EXPECTED_CHECKSUM="\$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_CHECKSUM="\$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
    if [ "\$EXPECTED_CHECKSUM" != "\$ACTUAL_CHECKSUM" ]; then
        echo 'Composer installer corrupted' >&2
        rm composer-setup.php
        exit 1
    fi
    php composer-setup.php --install-dir=/usr/bin --filename=composer
    rm composer-setup.php
fi
SCRIPT

info "Starting and enabling services"
remote <<SCRIPT
rc-update add nginx default
rc-update add php-fpm default
rc-service nginx start
rc-service php-fpm start
SCRIPT

info "Configuring firewall (allow HTTP/HTTPS/SSH)"
remote <<SCRIPT
apk add iptables ip6tables ipset
cat > /etc/iptables.rules <<'IPTABLES'
*filter
:INPUT DROP [0:0]
:FORWARD DROP [0:0]
:OUTPUT ACCEPT [0:0]
-A INPUT -i lo -j ACCEPT
-A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT
-A INPUT -p tcp --dport 22 -j ACCEPT
-A INPUT -p tcp --dport 80 -j ACCEPT
-A INPUT -p tcp --dport 443 -j ACCEPT
-A INPUT -j DROP
COMMIT
IPTABLES
iptables-restore < /etc/iptables.rules
ip6tables -P INPUT DROP
ip6tables -P FORWARD DROP
ip6tables -P OUTPUT ACCEPT
SCRIPT

ok "Server setup complete!"
echo ""
echo "Next steps:"
echo "  1. Point your domain's DNS A record to this server's IP"
echo "  2. Obtain SSL cert: acme.sh --issue -d sms.mikr.us -w /var/www/sms/app/public"
echo "  3. Edit deploy/inventory with your secrets"
echo "  4. Run: ./deploy/deploy.sh"

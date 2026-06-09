FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libssl-dev \
    && docker-php-ext-install intl opcache \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY --from=node:20-alpine /usr/local/bin/node /usr/local/bin/node
COPY --from=node:20-alpine /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

WORKDIR /app

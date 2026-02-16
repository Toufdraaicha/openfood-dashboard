# syntax=docker/dockerfile:1

FROM dunglas/frankenphp:1.3.6-php8.4-alpine AS base

WORKDIR /app

RUN install-php-extensions \
    apcu \
    intl \
    opcache \
    pdo_pgsql \
    redis \
    zip \
    gd \
    mbstring

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── Dev ──────────────────────────────────────────────────────────────────────
FROM base AS dev

ENV APP_ENV=dev
ENV XDEBUG_MODE=off

RUN install-php-extensions xdebug
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY docker/php/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/app.dev.ini
COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--watch"]

# ── Prod ─────────────────────────────────────────────────────────────────────
FROM base AS prod

ENV APP_ENV=prod
ENV APP_DEBUG=0

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/app.prod.ini

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader

COPY . .

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && php bin/console cache:warmup

CMD ["frankenphp", "run"]

# syntax=docker/dockerfile:1

FROM dunglas/frankenphp:1-php8.4 AS base

RUN install-php-extensions \
    pdo_pgsql pgsql redis intl zip opcache apcu bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# ── Dev ──────────────────────────────────────────
FROM base AS dev

ENV APP_ENV=dev

RUN install-php-extensions xdebug
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY docker/frankenphp/Caddyfile /etc/caddy/Caddyfile
COPY docker/frankenphp/php.ini $PHP_INI_DIR/conf.d/99-custom.ini

# Hot reload natif FrankenPHP
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--watch"]

# ── Prod ─────────────────────────────────────────
FROM base AS prod

ENV APP_ENV=prod

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY . .
RUN composer install --no-dev --optimize-autoloader \
    && php bin/console cache:warmup

COPY docker/frankenphp/Caddyfile /etc/caddy/Caddyfile
COPY docker/frankenphp/php.ini $PHP_INI_DIR/conf.d/99-custom.ini

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

# syntax=docker/dockerfile:1.7
# ================================================================
# Torex Guard - Multi-stage Dockerfile (MySQL + PHP-FPM 8.3)
# ================================================================

# -- Base PHP image with the extensions Laravel + Torex needs ----
FROM php:8.3-fpm-alpine AS base

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

WORKDIR /var/www/html

# System packages: nginx + supervisor for the app image,
# build deps for PHP extensions, runtime libs.
RUN apk add --no-cache \
    nginx \
    supervisor \
    bash \
    curl \
    git \
    icu-data-full \
    libzip \
    libpng \
    libjpeg-turbo \
    libwebp \
    freetype \
    oniguruma \
    icu-libs \
    tini \
    tzdata \
 && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    oniguruma-dev \
    icu-dev \
    autoconf \
 && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        mbstring \
        zip \
        bcmath \
        pcntl \
        intl \
        gd \
        opcache \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apk del .build-deps \
 && rm -rf /tmp/* /var/cache/apk/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP/opcache tuning shipped in the image
COPY docker/php.ini /usr/local/etc/php/conf.d/torex.ini

# ---------------------------------------------------------------
# vendor stage: install composer deps in isolation for caching
# ---------------------------------------------------------------
FROM base AS vendor

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

# ---------------------------------------------------------------
# production stage: full app + nginx + php-fpm + supervisor
# ---------------------------------------------------------------
FROM base AS production

# Bring in installed vendor early so its mtime stays cached.
COPY --from=vendor /var/www/html/vendor ./vendor

# App source
COPY . .

# Finalise composer autoload now that the source is in place.
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R ug+rwX storage bootstrap/cache

COPY docker/nginx.conf       /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Cache config/routes/views at image build time so cold-start is fast.
# These are no-ops if APP_KEY isn't set yet; the entrypoint will re-run them.
RUN php artisan config:cache  || true \
 && php artisan route:cache   || true \
 && php artisan view:cache    || true

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS http://127.0.0.1/up || exit 1

ENTRYPOINT ["/sbin/tini", "--"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# ---------------------------------------------------------------
# development stage: code is bind-mounted, run artisan serve
# ---------------------------------------------------------------
FROM base AS development

RUN apk add --no-cache nodejs npm \
 && pecl install xdebug \
 && docker-php-ext-enable xdebug

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-interaction

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

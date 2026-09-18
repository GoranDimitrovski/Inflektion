FROM php:8.4.7-cli-alpine

RUN apk add --no-cache \
        git \
        unzip \
        libpq \
        postgresql-dev \
        icu-libs \
        icu-dev \
        oniguruma-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql pgsql intl mbstring bcmath opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del postgresql-dev icu-dev oniguruma-dev $PHPIZE_DEPS

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini

COPY --from=composer:2.8.9 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# No COPY/composer install here: docker-compose.yml bind-mounts ./api over
# /app for every real use of this image (local dev and CI alike), which
# shadows anything baked in at this point — so building it in just wastes
# time on every image build. `composer install` runs at container start
# instead (see the Makefile / CI), writing straight into the bind mount.

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000", "--no-reload"]

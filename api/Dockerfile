FROM php:8.4.7-cli-alpine

RUN apk add --no-cache \
        git \
        unzip \
        libpq \
        postgresql-dev \
        icu-libs \
        icu-dev \
        oniguruma-dev \
    && docker-php-ext-install pdo_pgsql pgsql intl mbstring bcmath opcache \
    && apk del postgresql-dev icu-dev oniguruma-dev

COPY --from=composer:2.8.9 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-interaction --prefer-dist --no-progress

COPY . .

RUN composer dump-autoload --optimize

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000", "--no-reload"]

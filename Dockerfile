FROM php:8.2-cli

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip ca-certificates pkg-config libzip-dev libcurl4-openssl-dev libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite curl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . /app

RUN composer install --no-dev --optimize-autoloader \
    && if [ ! -f /app/config.php ]; then cp /app/config.example.php /app/config.php; fi \
    && mkdir -p /app/storage/db /app/storage/logs /app/public/uploads/passports /app/public/uploads/contracts /app/public/uploads/receipts \
    && chmod -R 775 /app/storage /app/public/uploads

EXPOSE 10000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t public public/router.php"]

FROM php:8.4-fpm-alpine
WORKDIR /var/www/html
RUN apk add --no-cache bash curl openssl zip unzip git
# composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json /var/www/html/
RUN composer install --no-dev --prefer-dist --no-progress || true
# create storage folder
RUN mkdir -p /var/www/html/storage && chown -R www-data:www-data /var/www/html/storage
CMD ["php-fpm"]
# Stage 1: Laravel PHP App
FROM php:8.2-fpm AS app-asset

# Install PHP extension, composer, Laravel dsb
WORKDIR /var/www
COPY . .
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libssl-dev libpq-dev libicu-dev \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer \
    && composer install --no-interaction --prefer-dist --optimize-autoloader \
    && chown -R www-data:www-data /var/www

# Stage 2: Nginx
FROM nginx:latest AS nginx

COPY nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app-asset /var/www/public /var/www/public

EXPOSE 9000
CMD ["php-fpm"]

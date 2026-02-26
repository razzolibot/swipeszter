FROM php:8.4-fpm-alpine

# Rendszer + build függőségek
RUN apk add --no-cache \
    git curl zip unzip \
    libpng-dev libjpeg-turbo-dev libwebp-dev libzip-dev \
    libpq-dev \
    nodejs npm \
    ffmpeg nginx supervisor \
    autoconf g++ make linux-headers

# PHP kiterjesztések
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo pdo_mysql pdo_pgsql \
        gd zip bcmath opcache pcntl exif

# Redis (PECL)
RUN pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf g++ make linux-headers

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Composer install (layer cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# npm install + build
COPY package.json package-lock.json ./
RUN npm ci

COPY . .

# Assets build + composer dump (env vars nélkül, runtime-on lesz cache)
RUN composer dump-autoload --optimize \
    && npm run build

# Jogosultságok
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Nginx konfig
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Supervisor konfig
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Entrypoint script (migráció + cache runtime-on)
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]

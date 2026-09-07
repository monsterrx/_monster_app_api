FROM php:8.5-apache
WORKDIR /var/www/html/monsterApi
RUN apt-get update && apt-get install -y --no-install-recommends libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libzip-dev libicu-dev unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl gd intl \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache
COPY docker/apache2/sites-available/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite
EXPOSE 80
CMD ["apache2-foreground"]

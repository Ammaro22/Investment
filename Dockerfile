# المرحلة الأولى: بناء dependencies
FROM composer:2 AS build

WORKDIR /app
COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader


COPY . .

# المرحلة الثانية: تشغيل Laravel مع PHP-FPM
FROM php:8.2-fpm

# تثبيت ملحقات Laravel المطلوبة
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip unzip git curl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

WORKDIR /var/www/html

COPY --from=build /app ./

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

CMD ["php-fpm"]

# المرحلة الأولى: بناء dependencies
FROM composer:2 AS build

WORKDIR /app

# نسخ composer.json فقط (مش composer.lock)
COPY composer.json ./

# تثبيت الباكجات بدون الحاجة لـ composer.lock
RUN composer install --no-dev --optimize-autoloader

# نسخ باقي ملفات المشروع
COPY . .

# المرحلة الثانية: تشغيل Laravel مع PHP-FPM
FROM php:8.2-fpm

# تثبيت ملحقات PHP المطلوبة لـ Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip unzip git curl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

WORKDIR /var/www/html

# نسخ المشروع من المرحلة الأولى
COPY --from=build /app ./

# تعديل صلاحيات مجلدات التخزين و cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# تشغيل PHP-FPM
CMD ["php-fpm"]

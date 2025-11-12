# استخدم PHP 8.2
FROM php:8.2-fpm

# تحديد مجلد العمل
WORKDIR /var/www/html

# تثبيت المكتبات الأساسية وإضافات PHP المطلوبة
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install gd pdo_mysql zip

# تثبيت Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# نسخ ملفات Composer أولاً
COPY composer.json composer.lock ./

# تثبيت الاعتمادات
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# نسخ باقي المشروع
COPY . .

# صلاحيات مجلدات Laravel
RUN chown -R www-data:www-data storage bootstrap/cache

# تعيين متغير البيئة (لتجنب بعض التحذيرات)
ENV PORT=8080

# فتح المنفذ اللي Render بيستخدمه
EXPOSE 8080

# تشغيل Laravel (مع migration + seeding)
CMD php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=$PORT

# استخدم PHP 8.2 مع FPM
FROM php:8.2-fpm

# تعيين مجلد العمل
WORKDIR /var/www/html

# تثبيت المتطلبات
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_pgsql zip

# تثبيت Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# نسخ ملفات composer أولاً
COPY composer.json composer.lock ./

# تثبيت الاعتمادات بدون post-scripts
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# نسخ بقية ملفات المشروع
COPY . .

# تشغيل post-autoload بعد نسخ المشروع
RUN composer dump-autoload && php artisan package:discover --ansi || true

# ضبط الأذونات
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# تعيين متغير البيئة
ENV PORT=8080

# فتح البورت اللي Render بيستخدمه
EXPOSE 8080

# ✅ تشغيل migrate و seed أول مرة فقط
CMD if [ ! -f /var/www/html/.first_run_done ]; then \
      echo "🔹 Running initial migrate & seed..."; \
      php artisan migrate --force && \
      php artisan db:seed --force && \
      touch /var/www/html/.first_run_done; \
    else \
      echo "✅ Database already initialized, skipping migrate/seed."; \
    fi && \
    php artisan serve --host=0.0.0.0 --port=$PORT

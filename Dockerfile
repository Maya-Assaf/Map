# استخدام نسخة PHP 8.2 FPM (متوافقة تماماً مع متطلباتك)
FROM php:8.2-fpm

# تحديد مسار العمل داخل الحاوية
WORKDIR /var/www

# تثبيت الحزم الأساسية والاعتمادات المطلوبة (مثل مكتبات معالجة الصور لـ Excel)
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    libonig-dev \
    libxml2-dev

# تنظيف الكاش لتقليل حجم الحاوية
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# تثبيت إضافات PHP اللازمة لعمل لارافيل وحزمة Excel
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath xml
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# نسخ ملفات المشروع إلى الحاوية
COPY . /var/www

RUN composer install --no-interaction --optimize-autoloader --no-dev

# ضبط الصلاحيات لمجلدات التخزين (Storage) والكاش
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

RUN rm -f public/storage && php artisan storage:link

HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
  CMD wget -qO- http://localhost/ || exit 1

# فتح المنفذ 80
EXPOSE 80

# تشغيل خادم PHP-FPM
CMD ["php-fpm"]

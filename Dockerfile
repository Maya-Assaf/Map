# استخدام نسخة PHP 8.2 مع Apache المدمج
FROM php:8.2-apache

# 1. تثبيت الاعتمادات النظامية والمكتبات المطلوبة لـ Laravel و Excel
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
    libxml2-dev \
    wget

# تنظيف الكاش لتقليل حجم الحاوية
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# تثبيت إضافات PHP اللازمة لعمل لارافيل وحزمة Excel
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath xml
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# 4. تفعيل موديل Rewrite الخاص بـ Apache
RUN a2enmod rewrite

# 5. تغيير مسار الـ DocumentRoot الخاص بـ Apache ليشير إلى مجلد public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 6. تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 7. نسخ ملفات المشروع إلى الحاوية
WORKDIR /var/www/html
COPY . .

# 8. تثبيت مكتبات Composer (بدون سكربتات لتجنب الأخطاء)
RUN composer install --no-interaction --optimize-autoloader --no-dev --no-scripts

# --- [ الإضافة الجديدة لحل مشكلة الصلاحيات والكاش ] ---
# إنشاء المجلدات الفرعية المطلوبة بشكل مسبق للتأكد من وجودها قبل تطبيق الصلاحيات
RUN mkdir -p /var/www/html/storage/framework/cache/data \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/bootstrap/cache

# 9. ضبط الملكية والصلاحيات للمجلدات بشكل صحيح
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html/storage -type d -exec chmod 775 {} \; \
    && find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \; \
    && find /var/www/html/storage -type f -exec chmod 664 {} \; \
    && find /var/www/html/bootstrap/cache -type f -exec chmod 664 {} \;

# تجاوز خطأ الربط
RUN rm -f public/storage && php artisan storage:link || true

# 10. فحص حالة الحاوية (Healthcheck)
HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# المنفذ الافتراضي لـ Apache
EXPOSE 80

# تشغيل Apache
CMD ["apache2-foreground"]

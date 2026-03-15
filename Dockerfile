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

# 4. تفعيل موديل Rewrite الخاص بـ Apache (ضروري جداً لروابط لارافيل)
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

# 8. تثبيت مكتبات Composer
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 9. ضبط الصلاحيات لمجلدات Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache


RUN rm -f public/storage && php artisan storage:link


# 10. فحص حالة الحاوية (Healthcheck) على منفذ 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
  CMD curl -f http://localhost/ || exit 1

# المنفذ الافتراضي لـ Apache هو 80
EXPOSE 80

# تشغيل Apache في المقدمة
CMD ["apache2-foreground"]

FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev

# Install PHP extensions (intl + gd required by Filament)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_pgsql bcmath zip intl gd exif

# Install Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist

# Copy application
COPY . .

# Regenerate autoloader to include app-specific classes (no-scripts = skip artisan calls)
RUN php -d memory_limit=-1 /usr/bin/composer dump-autoload --no-scripts

# Strip UTF-8 BOM from PHP files (Windows editors sometimes add it, breaks namespace declarations)
RUN php -r "foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app')) as \$f){if(\$f->getExtension()!=='php')continue;\$c=file_get_contents(\$f->getPathname());if(substr(\$c,0,3)==='\xEF\xBB\xBF')file_put_contents(\$f->getPathname(),substr(\$c,3));}"

# Set permissions and clear any stale cached bootstrap files
RUN rm -f bootstrap/cache/*.php
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Nginx config
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Supervisor config
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Entrypoint script (strip Windows CRLF so shebang works on Linux)
COPY docker/entrypoint.sh /entrypoint.sh
RUN sed -i 's/\r$//' /entrypoint.sh && chmod +x /entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/entrypoint.sh"]

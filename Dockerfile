# syntax=docker/dockerfile:1.7

# ============================================================
# Tesfa Platform — Application Container
# ============================================================
FROM php:8.3-fpm-alpine

# System dependencies
RUN apk add --no-cache \
    bash \
    git \
    curl \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    icu-dev \
    mysql-client \
    nodejs \
    npm \
    supervisor \
    tzdata

# Timezone
ENV TZ=Africa/Addis_Ababa
RUN cp /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first (better Docker layer caching)
COPY composer.json composer.lock* ./

# Install PHP dependencies
RUN composer install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --no-scripts \
        --prefer-dist

# Copy the rest of the application
COPY . /var/www/html

# Re-run composer scripts now that all files are present
RUN composer run-script post-autoload-dump || true

# Install and build frontend assets (only if package.json exists)
RUN if [ -f package.json ]; then \
        npm ci --no-audit --no-fund && \
        npm run build && \
        rm -rf node_modules; \
    fi

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
FROM php:8.4-fpm

# Installing system dependencies (Added libwebp-dev)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    curl

# Installing PHP extensions (Added --with-webp and exif)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_pgsql gd zip intl bcmath exif

# Composer installation
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy project files
COPY . .

# Launching the built-in Laravel server on port 8000
CMD composer install && php artisan serve --host=0.0.0.0 --port=8000
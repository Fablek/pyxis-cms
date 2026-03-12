FROM php:8.4-fpm

# Installing system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    curl

# Installing PHP extensions (including pdo_pgsql for Postgres)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_pgsql gd zip intl bcmath

# Composer installation
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy project files (from main directory)
COPY . .

# Launching the built-in Laravel server on port 8000
CMD composer install && php artisan serve --host=0.0.0.0 --port=8000
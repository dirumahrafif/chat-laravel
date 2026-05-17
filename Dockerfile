# Stage 1: Build assets
FROM node:20-alpine AS assets-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: PHP Application
FROM dunglas/frankenphp:latest-php8.3-alpine

# Set working directory
WORKDIR /app

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    bash \
    git \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev

RUN install-php-extensions \
    pcntl \
    bcmath \
    gd \
    intl \
    zip \
    pdo_mysql \
    pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Copy built assets from Stage 1
COPY --from=assets-builder /app/public/build ./public/build

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Environment variables for production
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV FRANKENPHP_CONFIG="worker ./public/index.php"

# Expose port 80
EXPOSE 80

# Jalankan optimasi Laravel dan jalankan server
CMD sh -c "php artisan config:cache && php artisan route:cache && php artisan view:cache && if [ \"\$RUN_MIGRATIONS\" = \"true\" ]; then php artisan migrate --force; fi && frankenphp php-server -r public/"

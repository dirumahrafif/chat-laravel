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

# Install system dependencies
RUN apk add --no-cache \
    bash \
    git \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    libxml2-dev \
    sqlite-dev

# Install PHP extensions
RUN install-php-extensions \
    pcntl \
    bcmath \
    gd \
    intl \
    zip \
    pdo_mysql \
    pdo_pgsql \
    pdo_sqlite \
    mbstring \
    xml \
    dom \
    curl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Copy built assets from Stage 1
COPY --from=assets-builder /app/public/build ./public/build

# Install PHP dependencies
# Kita tambahkan --no-scripts dulu untuk menghindari error package:discover jika environment belum siap
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Environment variables for production
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV FRANKENPHP_CONFIG="worker ./public/index.php"

# Expose port 80
EXPOSE 80

# Jalankan optimasi Laravel dan jalankan server
CMD sh -c "php artisan package:discover --ansi && php artisan config:cache && php artisan route:cache && php artisan view:cache && if [ \"\$RUN_MIGRATIONS\" = \"true\" ]; then php artisan migrate --force; fi && frankenphp php-server -r public/"

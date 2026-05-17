# =========================
# Stage 1: Build Frontend Assets
# =========================
FROM node:20-alpine AS assets-builder

WORKDIR /app

COPY package*.json ./

RUN npm install

COPY . .

RUN npm run build


# =========================
# Stage 2: PHP + Apache
# =========================
FROM php:8.4-apache

WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    zip \
    git \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Install only required PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip

# Enable rewrite
RUN a2enmod rewrite

# Laravel public folder
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP deps
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --ignore-platform-reqs

# Copy source
COPY . .

# Copy frontend build
COPY --from=assets-builder /app/public/build ./public/build

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Laravel optimize
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s \
CMD curl -f http://localhost || exit 1

CMD ["apache2-foreground"]
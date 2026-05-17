FROM php:8.4-apache

WORKDIR /var/www/html

# Install dependency Linux
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libzip-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip

# Enable Apache rewrite
RUN a2enmod rewrite

# Copy project
COPY . .

# Set document root ke public Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf

# Permission
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
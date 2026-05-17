FROM php:8.3-fpm-alpine

# Install packages
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    git \
    unzip \
    zip \
    nodejs \
    npm \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    bcmath \
    gd \
    xml \
    zip \
    mbstring

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# App directory
WORKDIR /app

# Copy files
COPY . .

# Install PHP dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --ignore-platform-reqs

# Build frontend
RUN npm install && npm run build

# Laravel permissions
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache && \
    chown -R www-data:www-data /app && \
    chmod -R 775 storage bootstrap/cache

# Generate app key jika belum ada
RUN cp .env.example .env || true
RUN php artisan key:generate || true

# PHP-FPM listen TCP
RUN sed -i 's|listen = .*|listen = 9000|g' /usr/local/etc/php-fpm.d/www.conf

# Nginx config
RUN rm -f /etc/nginx/http.d/default.conf && \
echo 'server {
    listen 80;
    server_name _;
    root /app/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    client_max_body_size 100M;
}' > /etc/nginx/http.d/default.conf

# Supervisor config
RUN mkdir -p /etc/supervisor.d && \
echo '[supervisord]
nodaemon=true

[program:php-fpm]
command=php-fpm --nodaemonize
autostart=true
autorestart=true

[program:nginx]
command=nginx -g "daemon off;"
autostart=true
autorestart=true
' > /etc/supervisor.d/supervisord.ini

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor.d/supervisord.ini"]
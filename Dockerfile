FROM php:8.3-fpm-alpine

# Install system packages
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

# Set working directory
WORKDIR /app

# Copy application
COPY . .

# Install composer dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --ignore-platform-reqs

# Build frontend assets
RUN npm install && npm run build

# Ensure Laravel directories exist
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache

# Permissions
RUN chown -R www-data:www-data /app && \
    chmod -R 775 storage bootstrap/cache

# Generate app key if needed
RUN cp .env.example .env || true
RUN php artisan key:generate || true

# Configure PHP-FPM to listen on TCP
RUN sed -i 's|listen = .*|listen = 127.0.0.1:9000|g' \
    /usr/local/etc/php-fpm.d/www.conf

# Remove default nginx config
RUN rm -f /etc/nginx/http.d/default.conf

# Create nginx config
RUN cat > /etc/nginx/http.d/default.conf << 'EOF'
server {
    listen 80;
    server_name _;

    root /app/public;
    index index.php index.html;

    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;

        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;

        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    client_max_body_size 100M;
}
EOF

# Create supervisor config
RUN mkdir -p /etc/supervisor.d

RUN cat > /etc/supervisor.d/supervisord.ini << 'EOF'
[supervisord]
nodaemon=true

[program:php-fpm]
command=php-fpm --nodaemonize
autostart=true
autorestart=true
priority=5

[program:nginx]
command=nginx -g "daemon off;"
autostart=true
autorestart=true
priority=10
EOF

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor.d/supervisord.ini"]
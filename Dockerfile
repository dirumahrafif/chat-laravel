FROM php:8.4-fpm-alpine

# Install system dependencies & extension compiler tools
RUN apk add --no-cache \
    nginx \
    shadow \
    curl \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    nodejs \
    npm \
    oniguruma-dev

# Install PHP extensions yang dibutuhkan Laravel secara lengkap
RUN docker-php-ext-install pdo_mysql bcmath gd xml zip mbstring

# Install Composer terbaru
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Install dependencies dengan mengabaikan pengecekan platform lokal vps yang ketat
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Build frontend assets (Vite / Mix)
RUN npm install && npm run build

# Setup Nginx configuration inline
RUN echo 'server { \
    listen 80; \
    root /app/public; \
    index index.php index.html; \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    location ~ \.php$ { \
        try_files $uri =404; \
        fastcgi_split_path_info ^(.+\.php)(/.+)$; \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        include fastcgi_params; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
        fastcgi_param PATH_INFO $fastcgi_path_info; \
    } \
}' > /etc/nginx/http.d/default.conf

# === TAMBAHKAN DUA BARIS PERINTAH PERMISSION INI ===
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache
RUN chmod -R 775 /app/storage /app/bootstrap/cache

EXPOSE 80

# Jalankan PHP-FPM dan Nginx bersamaan
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
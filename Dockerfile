# Stage 1: Build assets with Node
#FROM node:20 AS node-builder

#WORKDIR /app

#COPY package*.json ./
#RUN npm install

#COPY . .
#RUN npm run build

# Stage 2: Laravel + PHP
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl gd

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy source files
#COPY . .

# Copy built assets from node stage
#COPY --from=node-builder /app/public/build ./public/build

COPY . .
COPY ./deployment/.env .
# Set correct permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www

# Install PHP dependencies
RUN composer install -vvv --no-interaction --prefer-dist --optimize-autoloader

RUN php artisan storage:link && \
    chown -R www-data:www-data /var/www

# Expose port
EXPOSE 9000

CMD ["php-fpm"]

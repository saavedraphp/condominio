###############################################################################
# 🧱 ETAPA 1 ─ Build de frontend (Node + Vite)                                 #
###############################################################################
FROM node:20-alpine AS node-builder
WORKDIR /app

# 1) Caché de dependencias de Node
COPY package*.json ./
RUN npm install --frozen-lockfile

# 2) Copiar archivos que Vite necesita para compilar (config, tsconfig) y recursos
COPY vite.config.* ./
COPY resources ./resources

# 3) Ejecutar build de Vite → genera /app/public/build
RUN npm run build


###############################################################################
# 🧱 ETAPA 2 ─ Build de backend (PHP + extensiones + Composer)                 #
###############################################################################
FROM php:8.2-fpm-alpine AS php-builder

# 1️⃣  Librerías runtime mínimas necesarias para PHP y extensiones
RUN apk add --no-cache \
    curl zip unzip git bash \
    libpng libjpeg-turbo freetype libwebp \
    libxml2 libzip oniguruma imagemagick icu-libs

# 2️⃣  Paquetes *-dev* y toolchain para compilar todas las extensiones
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS pkgconf \
    zlib-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev libwebp-dev \
    libzip-dev \
    mariadb-dev \
    oniguruma-dev \
    libxml2-dev \
    icu-dev \
    imagemagick-dev \
    libsodium-dev

# 3️⃣  Compilar extensiones internas requeridas por Laravel
RUN docker-php-ext-install \
      gd \
      mbstring \
      pdo_mysql \
      zip \
      exif \
      pcntl \
      intl \
      xml \
      bcmath \
      sodium

# 4️⃣  Instalar Redis e Imagick vía PECL y habilitarlas
RUN pecl install redis \
 && docker-php-ext-enable redis \
 && pecl install imagick \
 && docker-php-ext-enable imagick

# 5️⃣  Eliminar toolchain para aligerar la imagen
RUN apk del .build-deps

# 6️⃣  Copiar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 6.1) Copiar TODO el proyecto Laravel (controladores, vistas, config, rutas, artisan, etc.)
COPY . .
COPY /deployment/.env ./

# 6.2) Ejecutar Composer install una vez que el código completo esté presente
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
 && php artisan storage:link \
 && chown -R www-data:www-data /var/www

# 6.3) Copiar los assets compilados desde node-builder
COPY --from=node-builder /app/public/build ./public/build


###############################################################################
# 🚀 ETAPA 3 ─ Runtime ligera                                                   #
###############################################################################
FROM php:8.2-fpm-alpine AS runtime

# 1️⃣  Librerías runtime mínimas (sin *-dev*)
RUN apk add --no-cache \
    libpng libjpeg-turbo freetype libwebp \
    libxml2 libzip oniguruma imagemagick icu-libs

# 2️⃣  Copiar extensiones compiladas y configuración de PHP
COPY --from=php-builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=php-builder /usr/local/etc/php/conf.d   /usr/local/etc/php/conf.d

# 3️⃣  Copiar código completo (incluye vendor/ y public/build)
COPY --from=php-builder /var/www /var/www

WORKDIR /var/www
EXPOSE 9000
CMD ["php-fpm"]

# ────────────────────────────────────────────────────────────────
# Stage 1: extraemos sólo los ficheros estáticos de la imagen PHP
# ────────────────────────────────────────────────────────────────
FROM condominio-app:latest AS php-static

# Asumimos que tu imagen PHP expone /var/www/public con todo el código
WORKDIR /var/www/public

# Copiamos sólo assets y vendor desde esa imagen
# (esto toma el snapshot de /var/www/public/assets y /var/www/public/vendor)
RUN mkdir -p /static && \
    cp -a assets /static/assets && \
    cp -a vendor /static/vendor

# ────────────────────────────────────────────────────────────────
# Stage 2: construimos la imagen final de Nginx con esos ficheros
# ────────────────────────────────────────────────────────────────
FROM nginx:stable-alpine

# Directorio donde Nginx servirá tu app
WORKDIR /usr/share/nginx/html

# 1) Volcamos los estáticos que copiamos antes
COPY --from=php-static /static/assets ./assets
COPY --from=php-static /static/vendor ./vendor

# 2) Añadimos la configuración de Nginx
COPY nginx/default.conf /etc/nginx/conf.d/default.conf

# (Opcional) expón el puerto
EXPOSE 80

# Nginx ya arranca por defecto con CMD ["nginx", "-g", "daemon off;"]

# Apache + PHP 8.4
FROM php:8.4.13-apache

# Habilitar mod_rewrite e ajustar DocumentRoot (mude para /var/www/html se não usa /public)
RUN a2enmod rewrite \
 && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf || true \
 && sed -ri 's!<Directory /var/www/>!<Directory /var/www/html/>!g' /etc/apache2/apache2.conf || true \
 && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf || true

# Extensões do PostgreSQL
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

# Opcional: timezone e opcache
RUN echo "date.timezone=America/Sao_Paulo" > /usr/local/etc/php/conf.d/timezone.ini \
 && echo "opcache.enable=1" > /usr/local/etc/php/conf.d/opcache.ini

# (sem COPY) – vamos montar sua pasta via volume
WORKDIR /var/www/html
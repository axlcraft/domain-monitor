# Latest stable PHP + Apache
FROM php:apache

# Build deps for requested PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
      libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
      libxml2-dev libcurl4-openssl-dev libonig-dev pkg-config unzip git tzdata \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" \
         pdo pdo_mysql mysqli intl zip gd bcmath mbstring curl xml \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Set Apache DocumentRoot to /var/www/html/public
ARG APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf

# Apache timeout only (no proxy needed)
RUN set -eux; \
    echo "Timeout 300" > /etc/apache2/conf-available/timeouts.conf; \
    a2enconf timeouts

WORKDIR /var/www/html


# /var/www/logistics/api/Dockerfile

FROM php:8.2-apache

# System-Pakete, PHP-Extensions & Composer installieren
RUN apt-get update && apt-get install -y \
      libpq-dev libxml2-dev unzip git \
    && docker-php-ext-install pdo pdo_pgsql xml \
    && pecl install apcu && docker-php-ext-enable apcu \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# GD mit FreeType, JPEG und PNG nachinstallieren
RUN apt-get update \
    && apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache-Module aktivieren
RUN a2enmod rewrite headers

WORKDIR /var/www/html

# Nur composer.json & composer.lock kopieren
COPY composer.json composer.lock ./

# Dependencies ohne Scripts installieren (kein bin/console nötig)
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --no-scripts --optimize-autoloader

# Anschließend gesamten Code kopieren
COPY . .

# DocumentRoot auf public/ umstellen & .htaccess erlauben
RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#g' /etc/apache2/sites-available/000-default.conf \
 && sed -i 's#<Directory /var/www/html>#<Directory /var/www/html/public>#g' /etc/apache2/apache2.conf \
 && sed -i '/<Directory \/var\/www\/html\/public>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Autoloader & Cache manuell aufsetzen
RUN composer dump-autoload --optimize \
 && php bin/console cache:clear --env=prod --no-warmup \
 && php bin/console cache:warmup --env=prod

# Logs/Cache-Verzeichnisse anlegen & Rechte setzen
RUN mkdir -p var/cache var/log && chown -R www-data:www-data var

EXPOSE 80

CMD ["apache2-foreground"]

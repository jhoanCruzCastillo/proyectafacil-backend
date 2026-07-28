FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
        libpq-dev \
        libicu-dev \
        libzip-dev \
        libonig-dev \
        unzip \
        git \
    && docker-php-ext-install pdo pdo_pgsql pgsql intl mbstring zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

COPY . .

RUN mkdir -p writable/cache writable/logs writable/session writable/uploads \
    && chown -R www-data:www-data writable \
    && chmod -R 775 writable

RUN { \
        echo '<Directory /var/www/html/public>'; \
        echo '    AllowOverride All'; \
        echo '    Require all granted'; \
        echo '</Directory>'; \
    } > /etc/apache2/conf-available/ci4-public.conf \
    && a2enconf ci4-public \
    && sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' /etc/apache2/sites-available/000-default.conf

# The base image ships no php.ini at all, so PHP falls back to its own compiled-in defaults —
# notably log_errors=Off, which meant fatal errors vanished silently (no stderr, no error_log,
# nothing) instead of surfacing anywhere. Also raise memory_limit; CI4's bootstrap (autoloader +
# full route/config discovery) can be tight against the 128M compiled-in default.
RUN { \
        echo 'log_errors = On'; \
        echo 'error_log = /dev/stderr'; \
        echo 'display_errors = Off'; \
        echo 'memory_limit = 256M'; \
    } > /usr/local/etc/php/conf.d/proyecta-facil.ini

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]

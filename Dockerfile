FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        zip \
        unzip \
        curl \
        oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        gd \
        zip \
        mbstring \
        exif \
        bcmath \
        opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Pass environment variables through to PHP-FPM workers
RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/www.conf

# Session config
RUN echo "session.save_path = /Sites/byabsayee/storage/sessions" \
        > /usr/local/etc/php/conf.d/byabsayee.ini \
 && echo "session.gc_probability = 1" \
        >> /usr/local/etc/php/conf.d/byabsayee.ini

WORKDIR /Sites/byabsayee

COPY . /Sites/byabsayee/

RUN mkdir -p /Sites/byabsayee/storage/sessions \
             /Sites/byabsayee/uploads \
 && chmod -R 775 /Sites/byabsayee/storage \
                 /Sites/byabsayee/uploads

# Entrypoint fixes named-volume ownership (root→www-data) before php-fpm starts
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
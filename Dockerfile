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

# Set session save path to a folder inside the mounted volume
RUN echo "session.save_path = /Sites/byabsayee/storage/sessions" \
        > /usr/local/etc/php/conf.d/byabsayee.ini \
 && echo "session.gc_probability = 1" \
        >> /usr/local/etc/php/conf.d/byabsayee.ini

WORKDIR /Sites/byabsayee

EXPOSE 9000

CMD ["php-fpm"]

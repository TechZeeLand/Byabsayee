FROM php:8.2-fpm-alpine

# Install system dependencies and PHP extensions needed by Byabsayee
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

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /Sites/byabsayee

# PHP-FPM will listen on port 9000 inside the container
EXPOSE 9000

CMD ["php-fpm"]

# Make environment variables available to PHP-FPM workers
RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/www.conf

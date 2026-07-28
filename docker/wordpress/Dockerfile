FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libwebp-dev \
        libfreetype6-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
        unzip \
        git \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" \
        mysqli \
        pdo_mysql \
        gd \
        zip \
        exif \
        intl \
        mbstring \
        opcache \
    && a2enmod rewrite \
    && usermod -u 1000 www-data \
    && groupmod -g 1000 www-data

COPY php.ini /usr/local/etc/php/conf.d/zz-local.ini

RUN curl -o /usr/local/bin/wp -L https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x /usr/local/bin/wp

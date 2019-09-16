FROM php:7.3-cli as build

RUN apt-get update && apt-get upgrade -y \
    libzip-dev \
    unzip \
    libmcrypt-dev \
    zlib1g-dev \
    && docker-php-ext-install \
    iconv \
    mbstring \
    zip \
    bcmath

RUN pecl install mongodb && docker-php-ext-enable mongodb

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /app

RUN sh /app/bin/build.sh

CMD [ "php", "-a" ]
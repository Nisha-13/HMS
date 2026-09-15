FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring bcmath

RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

RUN chown -R www-data:www-data storage bootstrap/cache

CMD ["php-fpm", "-F"]
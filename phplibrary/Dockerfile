FROM composer:2.2.6 as composer
FROM php:8.1-fpm

# Set working directory
WORKDIR /var/www

# Install dependencies
RUN apt-get update && \
    apt-get install --no-install-recommends --yes \
        build-essential=12.9 \
        curl=7.74.0-1.3+deb11u3 \
        gifsicle=1.92-2+b1 \
        git=1:2.30.2-1 \
        jpegoptim=1.4.6-1 \
        libfreetype6-dev=2.10.4+dfsg-1+deb11u1 \
        libjpeg62-turbo-dev=1:2.0.6-4 \
        libonig-dev=6.9.6-1.1 \
        libpng-dev=1.6.37-3 \
        libpq-dev=13.8-0+deb11u1 \
        libxml2-dev=2.9.10+dfsg-6.7+deb11u2 \
        libzip-dev=1.7.3-1 \
        locales=2.31-13+deb11u4 \
        nginx=1.18.0-6.1+deb11u2 \
        optipng=0.7.7-1+b1 \
        pngquant=2.13.1-1 \
        unzip=6.0-26+deb11u1 \
        zip=3.0-12 && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Install extensions
RUN docker-php-ext-install \
        dom \
        exif \
        gd \
        mbstring \
        pcntl \
        soap \
        xml \
        zip && \
    docker-php-ext-configure gd --with-freetype --with-jpeg

# Install composer
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

# Use www-data (nginx) user for laravel
RUN mkdir vendor && \
    chown -R www-data: vendor /var/www && \
    chmod 700 vendor

# Copy existing application directory permissions
COPY --chown=www-data:www-data . ./
COPY --chown=www-data:www-data ./docker/nginx/conf.d/app.conf /etc/nginx/conf.d
COPY --chown=www-data:www-data ./docker/nginx/nginx.conf /etc/nginx/nginx.conf

# Forward request and error logs to docker log collector/stdout/stderr
RUN ln -sf /dev/stdout /var/log/nginx/access.log && \
    ln -sf /dev/stderr /var/log/nginx/error.log

# The nginx user must own the etc directory to tweak the nginx config
# source: https://github.com/nginxinc/docker-nginx-unprivileged/blob/main/Dockerfile-debian.template
RUN chown -R www-data:root /etc/nginx && \
    chmod -R g+w /etc/nginx

# Disable default nginx site
RUN rm /etc/nginx/sites-enabled/default

# Change current user to www
USER www-data

RUN composer install

CMD ["php-fpm"]

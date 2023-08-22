FROM php:8.2-apache
RUN apt-get update \
	&& apt-get install -y \
		libfreetype-dev \
		libjpeg62-turbo-dev \
		libpng-dev \
		libzip-dev \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg \
	&& docker-php-ext-install -j$(nproc) gd \
	&& docker-php-ext-install zip \
	&& a2enmod rewrite


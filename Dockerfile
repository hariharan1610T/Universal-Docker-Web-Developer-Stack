FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libssl-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions for MySQL and PostgreSQL
RUN docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql pgsql

# Install MongoDB PHP extension via PECL
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Fix Apache ServerName warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Enable Apache mod_rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html
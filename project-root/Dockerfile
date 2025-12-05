# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Enable Apache Rewrite
RUN a2enmod rewrite

# Install system dependencies + PHP extensions
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    && docker-php-ext-install pdo pdo_mysql zip gd mbstring xml bcmath

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# ---- IMPORTANT FIX ----
# Copy only composer files first
COPY composer.json composer.lock ./


# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy project files
COPY . .

# Set Apache Document Root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess inside public
RUN printf "<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n" >> /etc/apache2/apache2.conf

EXPOSE 80
CMD ["apache2-foreground"]

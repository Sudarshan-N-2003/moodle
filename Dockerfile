FROM php:8.1-apache

# Install dependencies and PHP extensions required by Moodle
RUN apt-get update && apt-get install -y \
    git unzip wget libpng-dev libjpeg-dev libpq-dev libxml2-dev \
    libicu-dev zlib1g-dev libzip-dev curl ca-certificates \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install gd intl pdo pdo_pgsql xml zip opcache

# Enable Apache rewrite
RUN a2enmod rewrite

# Copy Moodle code
COPY . /var/www/html

# Ensure moodledata directory exists (Render should mount disk here)
RUN mkdir -p /var/moodledata \
    && chown -R www-data:www-data /var/moodledata /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]

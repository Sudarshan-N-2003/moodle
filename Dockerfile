FROM php:8.1-apache

# 1. Install dependencies, including Postgres drivers and Cron
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libpq-dev libicu-dev libzip-dev \
    zlib1g-dev libonig-dev \
    cron supervisor git unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd intl pdo pdo_pgsql zip opcache soap bcmath sockets \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Configure PHP settings for Moodle
RUN echo "max_input_vars = 5000" > /usr/local/etc/php/conf.d/moodle.ini \
    && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/moodle.ini \
    && echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/moodle.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/moodle.ini

# 3. Download Moodle (Use a specific version branch)
WORKDIR /var/www/html
RUN rm -rf * \
    && git clone -b MOODLE_403_STABLE --depth 1 https://github.com/moodle/moodle.git . \
    && chown -R www-data:www-data /var/www/html

# 4. Copy custom config and supervisor setup
COPY config.php /var/www/html/config.php
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 5. Setup Cron for Moodle
RUN echo "* * * * * /usr/local/bin/php /var/www/html/admin/cli/cron.php > /dev/null 2>&1" > /etc/cron.d/moodle-cron \
    && chmod 0644 /etc/cron.d/moodle-cron \
    && crontab /etc/cron.d/moodle-cron

# 6. Start Supervisor (runs both Apache and Cron)
CMD ["/usr/bin/supervisord"]

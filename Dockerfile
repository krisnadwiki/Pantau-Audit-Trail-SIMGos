FROM php:8.3-apache

# Install extensions & utilities
RUN apt-get update && apt-get install -y \
    curl \
    git \
    unzip \
    zip \
    libzip-dev \
    && docker-php-ext-install zip pdo pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Set DocumentRoot to app/public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/app/public|g' \
    /etc/apache2/sites-available/000-default.conf

# Allow .htaccess overrides for public/ and expose api/ folder
RUN echo '<Directory /var/www/html/app/public>\n\
    AllowOverride All\n\
    Require all granted\n\
    Options -Indexes +FollowSymLinks\n\
</Directory>\n\
Alias /api /var/www/html/app/api\n\
<Directory /var/www/html/app/api>\n\
    AllowOverride None\n\
    Require all granted\n\
    Options -Indexes +FollowSymLinks\n\
</Directory>' >> /etc/apache2/sites-available/000-default.conf

# Enable mod_alias (needed for Alias directive)
RUN a2enmod alias

WORKDIR /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
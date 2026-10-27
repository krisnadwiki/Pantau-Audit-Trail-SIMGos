FROM webdevops/php-apache:8.3

# Konfigurasi Apache & PHP Environment
ENV WEB_DOCUMENT_ROOT=/app/public
ENV WEB_DOCUMENT_INDEX=index.php
ENV PHP_DATE_TIMEZONE=Asia/Jakarta

# Konfigurasi Apache vhost untuk alias /api dan rewrite
COPY docker/vhost-pantau.conf /opt/docker/etc/httpd/vhost.common.d/pantau.conf

# Salin source code aplikasi
COPY app /app

WORKDIR /app

EXPOSE 80
FROM webdevops/php-apache:8.3

# Konfigurasi Apache & PHP Environment
ENV WEB_DOCUMENT_ROOT=/app/public
ENV WEB_DOCUMENT_INDEX=index.php
ENV PHP_DATE_TIMEZONE=Asia/Jakarta

# Security Hardening: cegah kebocoran error dan info versi PHP
ENV PHP_DISPLAY_ERRORS=0
ENV PHP_EXPOSE_PHP=0

# Konfigurasi Apache vhost untuk alias /api, rewrite, dan proteksi file
COPY docker/security-global.conf /opt/docker/etc/httpd/conf.d/security-global.conf
COPY docker/vhost-pantau.conf /opt/docker/etc/httpd/vhost.common.d/pantau.conf

# Salin source code aplikasi
COPY app /app

WORKDIR /app

EXPOSE 80
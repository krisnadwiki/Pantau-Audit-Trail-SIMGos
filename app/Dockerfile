FROM webdevops/php-apache:8.3

ENV WEB_DOCUMENT_ROOT=/app/public
ENV WEB_DOCUMENT_INDEX=index.php
ENV PHP_DATE_TIMEZONE=Asia/Jakarta

COPY security-global.conf /opt/docker/etc/httpd/conf.d/security-global.conf
COPY apache.conf /opt/docker/etc/httpd/vhost.common.d/pantau.conf

COPY . /app

WORKDIR /app

EXPOSE 80

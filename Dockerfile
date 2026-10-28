FROM php:8.3-apache

# Only public/ is reachable from the web, src/ and views/ stay private.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    DATA_DIR=/data \
    TZ=Europe/Bratislava

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && mkdir -p /data \
    && chown www-data:www-data /data

COPY src/ /var/www/html/src/
COPY lang/ /var/www/html/lang/
COPY views/ /var/www/html/views/
COPY public/ /var/www/html/public/

VOLUME ["/data"]

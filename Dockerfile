FROM php:8.2-apache-bullseye


RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2enmod rewrite

# Your app is inside blog_system folder
COPY ./blog_system/ /var/www/html/

WORKDIR /var/www/html/

RUN chown -R www-data:www-data /var/www/html

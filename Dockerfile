# Use an official PHP + Apache image
FROM php:8.2-apache

# Install mysqli and PDO extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy everything (so includes/ is available)
COPY . /var/www/

# Move the public folder into Apache's web root
RUN rm -rf /var/www/html && ln -s /var/www/public /var/www/html

EXPOSE 80

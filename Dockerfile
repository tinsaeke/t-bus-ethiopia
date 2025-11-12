# Use an official PHP + Apache image
FROM php:8.2-apache

# Install mysqli and PDO extensions for MySQL support
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy your app files into the Apache web root
COPY . /var/www/html/

# Expose port 80 for web traffic
EXPOSE 80

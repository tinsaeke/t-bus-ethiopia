# Use an official PHP + Apache image
FROM php:8.2-apache

# Copy your app files into the container
COPY . /var/www/html/

# Expose port 80
EXPOSE 80

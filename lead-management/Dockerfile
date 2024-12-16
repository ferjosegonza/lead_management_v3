# Use the official PHP image with Apache
FROM php:8.0-apache

# Set the working directory
WORKDIR /var/www/html

# Copy the current directory contents into the container at /var/www/html
COPY . .

# Install Composer
RUN apt-get update && apt-get install -y \
    curl \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dependencies
RUN composer install

# Expose port 80
EXPOSE 80

# Start the Apache server
CMD ["apache2-foreground"]

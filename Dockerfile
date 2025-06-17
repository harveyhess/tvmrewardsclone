FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN apt-get update && apt-get install -y lsof
RUN docker-php-ext-install sockets
EXPOSE 80

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy your PHP app to Apache's public directory
COPY . /var/www/html
COPY ./includes /var/www/html/includes
COPY ./start.sh ./start.sh

# Make start.sh executable
RUN chmod +x ./start.sh

# Install and enable zip extension
RUN apt-get update && apt-get install -y libzip-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip

# Set working directory and start script
WORKDIR /var/www/html
CMD ["./start.sh"]
# Render PHP 8.2 Apache Deployment Dockerfile
FROM php:8.2-apache

# Install required system libraries
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli pdo_mysql gd zip

# Enable Apache modules: rewrite, headers
RUN a2enmod rewrite headers

# Reverse proxy configurations: prevent internal port leaking in redirects
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && echo "UseCanonicalName Off" >> /etc/apache2/apache2.conf \
    && echo "UseCanonicalPhysicalPort Off" >> /etc/apache2/apache2.conf

# Allow .htaccess in document root
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Set application directory
WORKDIR /var/www/html

# Copy all application files
COPY . /var/www/html/

# Copy and setup start.sh
COPY start.sh /usr/local/bin/start.sh
RUN tr -d '\r' < /usr/local/bin/start.sh > /usr/local/bin/start_clean.sh \
    && mv /usr/local/bin/start_clean.sh /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

# Set directory permissions for web server
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose Render web port
EXPOSE 10000 80

# Execute custom startup script
CMD ["/usr/local/bin/start.sh"]


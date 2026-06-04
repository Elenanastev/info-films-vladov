# ─────────────────────────────────────────────
# InfoFilms – PHP Web App
# Base: official PHP 8.2 with Apache
# ─────────────────────────────────────────────
FROM php:8.2-apache

# Install PDO MySQL extension (needed for database connection)
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite (useful for clean URLs)
RUN a2enmod rewrite

# Set working directory to Apache's document root
WORKDIR /var/www/html

# Copy all project source files into the container
COPY . .

# Create upload directory for posters and set permissions
RUN mkdir -p uploads/posters \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Allow .htaccess overrides for the document root
RUN sed -i 's/AllowOverride None/AllowOverride All/g' \
    /etc/apache2/apache2.conf

# Expose port 80 (Apache default)
EXPOSE 80

FROM php:7.4-apache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable necessary PHP extensions
RUN docker-php-ext-install pdo pdo_mysql json

# Enable Apache mod_rewrite for URL routing
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . .

# Install PHP dependencies with Composer
RUN composer install --no-dev --optimize-autoloader

# Create necessary directories
RUN mkdir -p /var/www/html/public/uploads
RUN mkdir -p /var/www/html/logs
RUN mkdir -p /var/www/html/tmp
RUN touch /var/www/html/tmp/osci-proof.csv

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html/public/uploads
RUN chmod -R 755 /var/www/html/logs
RUN chmod -R 775 /var/www/html/tmp
RUN chmod 664 /var/www/html/tmp/osci-proof.csv

# Configure Apache DocumentRoot
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN printf '%s\n' '<Directory /var/www/html/public>' '    AllowOverride All' '    Require all granted' '</Directory>' > /etc/apache2/conf-available/cake-shop-allowoverride.conf && \
    printf '%s\n' '<Directory /var/www/html/public/pages>' '    Options -Indexes' '    AllowOverride FileInfo Options' '    Require all granted' '</Directory>' > /etc/apache2/conf-available/cake-shop-pages-indexes.conf && \
    a2enconf cake-shop-allowoverride cake-shop-pages-indexes

# Create .htaccess for URL routing
RUN echo '<IfModule mod_rewrite.c>' > /var/www/html/public/.htaccess && \
    echo '    RewriteEngine On' >> /var/www/html/public/.htaccess && \
    echo '    RewriteCond %{REQUEST_FILENAME} !-f' >> /var/www/html/public/.htaccess && \
    echo '    RewriteCond %{REQUEST_FILENAME} !-d' >> /var/www/html/public/.htaccess && \
    echo '    RewriteRule ^(.*)$ index.php?$1 [QSA,L]' >> /var/www/html/public/.htaccess && \
    echo '</IfModule>' >> /var/www/html/public/.htaccess

RUN echo 'Options -Indexes' > /var/www/html/public/pages/.htaccess

EXPOSE 80

CMD ["apache2-foreground"]

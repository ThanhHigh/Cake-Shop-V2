FROM php:7.4-apache

# Enable necessary PHP extensions
RUN docker-php-ext-install pdo pdo_mysql json

# Enable Apache mod_rewrite for URL routing
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . .

# Create necessary directories
RUN mkdir -p /var/www/html/public/pages
RUN mkdir -p /var/www/html/public/uploads
RUN mkdir -p /var/www/html/tmp
RUN mkdir -p /var/www/html/logs

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 777 /var/www/html/public/uploads
RUN chmod -R 777 /var/www/html/public/pages
RUN chmod -R 777 /var/www/html/tmp
RUN chmod -R 777 /var/www/html/logs

# Configure Apache DocumentRoot
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
# RUN printf '%s\n' '<Directory /var/www/html/public>' '    AllowOverride All' '    Require all granted' '</Directory>' > /etc/apache2/conf-available/cake-shop-allowoverride.conf && \
#     printf '%s\n' '<Directory /var/www/html/public/pages>' '    Options -Indexes' '    AllowOverride FileInfo Options' '    Require all granted' '</Directory>' > /etc/apache2/conf-available/cake-shop-pages-indexes.conf && \
#     a2enconf cake-shop-allowoverride cake-shop-pages-indexes
RUN printf '%s\n' '<Directory /var/www/html/public>' '    AllowOverride All' '    Require all granted' '</Directory>' > /etc/apache2/conf-available/cake-shop-allowoverride.conf && \
    printf '%s\n' '<Directory /var/www/html/public/pages>' '    Options -Indexes' '    AllowOverride FileInfo Options Indexes' '    Require all granted' '</Directory>' > /etc/apache2/conf-available/cake-shop-pages-indexes.conf && \
    a2enconf cake-shop-allowoverride cake-shop-pages-indexes

# Create .htaccess for URL routing
RUN echo '<IfModule mod_rewrite.c>' > /var/www/html/public/.htaccess && \
    echo '    RewriteEngine On' >> /var/www/html/public/.htaccess && \
    echo '    RewriteCond %{REQUEST_FILENAME} !-f' >> /var/www/html/public/.htaccess && \
    echo '    RewriteCond %{REQUEST_FILENAME} !-d' >> /var/www/html/public/.htaccess && \
    echo '    RewriteRule ^(.*)$ index.php?$1 [QSA,L]' >> /var/www/html/public/.htaccess && \
    echo '</IfModule>' >> /var/www/html/public/.htaccess

RUN echo 'Options -Indexes' > /var/www/html/public/pages/.htaccess

# Let others php extensions be able to execute
RUN echo 'AddType application/x-httpd-php .php7 .php5 .php4 .php3 .phtml' >> /etc/apache2/apache2.conf

EXPOSE 80

CMD ["apache2-foreground"]

# Gamiton nato ang official PHP image nga naay Apache web server
FROM php:8.2-apache

# I-install ang PostgreSQL driver para maka-connect ang PDO sa Supabase
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# I-enable ang URL rewriting (good practice para sa mga web apps)
RUN a2enmod rewrite

# Kopyahon ang tanan nimong files (index.php, login.php, etc.) pasulod sa server
COPY . /var/www/html/

# Ablihan ang port 80 para makita ang website
EXPOSE 80

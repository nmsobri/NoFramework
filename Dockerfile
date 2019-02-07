# Base image
FROM ubuntu:16.04

# Set cli mode to non interactive
ENV DEBIAN_FRONTEND noninteractive

# Maintainer
MAINTAINER Sobri Kamal <normohdsobri@aemulus.com>

# Update sources and install locale
RUN apt-get update && apt-get install -y locales && locale-gen en_US.UTF-8

# Set environment for locale
ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8

# Update sources and add 3rd party repo to install php
RUN apt-get update && apt-get install -y software-properties-common python-software-properties && \
    add-apt-repository ppa:ondrej/php && add-apt-repository ppa:ondrej/apache2

# Install necessary tools and php
RUN apt-get update && apt-get install -y git zip unzip apache2 php7.2

# Install php extension. This need to be in its own layer or else composer will complaint missing extension
RUN apt-get install -y php-curl php-mysql php-pgsql php-mongodb php-sqlite3 php-bcmath php-zip && \
    php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

# Set working dir
WORKDIR /var/www/html

# Copy source code to /var/www/html
COPY . .

# Composer intall, create .env file and remove default apache file
RUN composer install && cp system/data/cfg/.env.prod.dist system/data/cfg/.env && rm -f index.html

# Configure apache
RUN sed -i "s:DocumentRoot /var/www/html:DocumentRoot /var/www/html/public:g" /etc/apache2/sites-enabled/000-default.conf && \
    sed -i "/#Include /a \\\tDirectoryIndex index.php" /etc/apache2/sites-enabled/000-default.conf && \
    sed -i "s/AllowOverride None/AllowOverride All/g" /etc/apache2/apache2.conf && a2enmod rewrite

# Expose port for apache
EXPOSE 80

# Check database existence [exit if necessary], migrate the table and boot the app
ENTRYPOINT service apache2 restart && bash

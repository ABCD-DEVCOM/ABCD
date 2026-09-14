FROM php:8-apache

RUN apt-get update -y \
 && apt-get install -y libpng-dev libyaz-dev libxml2-dev zlib1g-dev libtidy-dev libxslt1-dev libyaz-dev libcurl4-openssl-dev \
 && rm -rf /var/lib/{apt,dpkg,cache,log}

RUN docker-php-ext-install pdo_mysql gd xsl curl \
 && pecl install yaz  \
 && (pecl install xmlrpc || pecl install xmlrpc-1.0.0RC3) \
 && echo 'extension=yaz.so' > /usr/local/etc/php/conf.d/docker-php-ext-yaz.ini \
 && echo 'extension=xmlrpc.so' > /usr/local/etc/php/conf.d/docker-php-ext-xmlrpc.ini \
 && echo 'display_errors=off\nlog_errors=on' > /usr/local/etc/php/conf.d/docker-php-logging.ini

RUN a2enmod proxy_http cgi rewrite ldap

COPY ./www/cgi-bin_Linux /opt/ABCD/www/cgi-bin
COPY ./www/htdocs /opt/ABCD/www/htdocs
COPY ./www/htdocs/central/config.php.template /opt/ABCD/www/htdocs/central/config.php
COPY ./docker/abcd/apache.conf /etc/apache2/sites-available/000-default.conf

RUN ln -s /var/opt/ABCD/bases /opt/ABCD/www/bases

WORKDIR /opt/ABCD

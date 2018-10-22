FROM quay.io/keboola/aws-cli
ARG AWS_SECRET_ACCESS_KEY
ARG AWS_ACCESS_KEY_ID
ARG AWS_SESSION_TOKEN
RUN /usr/bin/aws s3 cp s3://teradata-drivers-driverss3bucket-8b7jbdmserup/tdodbc1620-16.20.00.36-1.noarch.deb /tmp/tdodbc1620-16.20.00.36-1.noarch.deb


FROM php:7-cli

ARG COMPOSER_FLAGS="--prefer-dist --no-interaction"
ARG DEBIAN_FRONTEND=noninteractive
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_PROCESS_TIMEOUT 3600

WORKDIR /code/

COPY docker/php-prod.ini /usr/local/etc/php/php.ini
COPY docker/composer-install.sh /tmp/composer-install.sh

RUN apt-get update && apt-get install -y \
        git \
        locales \
        unzip \
        lib32stdc++6 \
        unixodbc \
        unixodbc-dev

RUN chmod +x /tmp/composer-install.sh \
	&& /tmp/composer-install.sh

COPY --from=0 /tmp/tdodbc1620-16.20.00.36-1.noarch.deb /tmp/tdodbc1620-16.20.00.36-1.noarch.deb
RUN dpkg -i /tmp/tdodbc1620-16.20.00.36-1.noarch.deb

COPY docker/odbc.ini /etc/odbc.ini
COPY docker/odbcinst.ini /etc/odbcinst.ini

ENV ODBCHOME = /opt/teradata/client/ODBC_64/
ENV ODBCINI = /opt/teradata/client/ODBC_64/odbc.ini
ENV ODBCINST = /opt/teradata/client/ODBC_64/odbcinst.ini
ENV LD_LIBRARY_PATH = /opt/teradata/client/ODBC_64/lib

ENV LANG=en_US.UTF-8
ENV LC_ALL=en_US.UTF-8
ENV LANGUAGE=en_US.UTF-8
RUN printf 'en_US.UTF-8 UTF-8\n' >> /etc/locale.gen \
    && locale-gen

RUN set -x \
    && docker-php-source extract \
    && cd /usr/src/php/ext/odbc \
    && phpize \
    && sed -ri 's@^ *test +"\$PHP_.*" *= *"no" *&& *PHP_.*=yes *$@#&@g' configure \
    && ./configure --with-unixODBC=shared,/usr \
    && docker-php-ext-install odbc \
    && docker-php-source delete

RUN docker-php-ext-install mbstring

## Composer - deps always cached unless changed
# First copy only composer files
COPY composer.* /code/
# Download dependencies, but don't run scripts or init autoloaders as the app is missing
RUN composer install $COMPOSER_FLAGS --no-scripts --no-autoloader
# copy rest of the app
COPY . /code/
# run normal composer - all deps are cached already
RUN composer install $COMPOSER_FLAGS

CMD ["php", "/code/src/run.php"]

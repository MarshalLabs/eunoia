# Eunoia local development image (E0-03). PHP 8.4 because PHP 8.5 is not yet
# supported by phpoffice/phpspreadsheet (see runbook).
FROM php:8.4-cli-bookworm

ARG UID=1000
ARG GID=1000

COPY --from=mlocati/php-extension-installer:2.7 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2.8 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip postgresql-client curl ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions calendar sockets pdo_pgsql pgsql pdo_mysql intl gd zip bcmath pcntl exif redis opcache

# Node 22 for upstream JS parity tests and `npx jofotara-kit validate` inside the container.
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

COPY php.ini /usr/local/etc/php/conf.d/zz-eunoia.ini

RUN (getent group ${GID} || groupadd -g ${GID} eunoia) \
    && useradd -m -u ${UID} -g ${GID} -s /bin/bash eunoia

USER eunoia
WORKDIR /var/www/html

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

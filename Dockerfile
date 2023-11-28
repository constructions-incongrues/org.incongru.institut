FROM php:8.1.0-cli-bullseye

# Install cecil
RUN apt-get update && apt-get install -y \
        bash \
        curl \
        git \
        gpg \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        zlib1g-dev && \
    docker-php-ext-install fileinfo && \
    docker-php-ext-install gd && \
    docker-php-ext-install mbstring && \
    curl -kL https://cecil.app/cecil.phar -o /usr/local/bin/cecil && chmod +x /usr/local/bin/cecil

ENTRYPOINT [ "cecil" ]
CMD [ "serve" ]
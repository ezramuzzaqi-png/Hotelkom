FROM dunglas/frankenphp:latest

RUN install-php-extensions \
    mysqli \
    pdo_mysql \
    zip \
    gd

COPY . /app

COPY Caddyfile /etc/frankenphp/Caddyfile

WORKDIR /app
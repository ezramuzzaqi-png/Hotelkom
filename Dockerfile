FROM dunglas/frankenphp:latest

RUN install-php-extensions \
    mysqli \
    pdo_mysql \
    zip \
    gd

COPY . /app
WORKDIR /app

EXPOSE 80
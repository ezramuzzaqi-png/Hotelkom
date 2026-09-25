FROM dunglas/frankenphp:latest

RUN install-php-extensions \
    mysqli \
    pdo_mysql \
    zip \
    gd

COPY . /app
WORKDIR /app

CMD sh -c "frankenphp php-server --listen :${PORT:-80} --root /app"
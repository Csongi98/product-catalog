FROM php:8.2-cli

RUN docker-php-ext-install pdo pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN curl -sS https://get.symfony.com/cli/installer | bash - && \
    mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]

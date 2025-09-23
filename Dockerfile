FROM php:8.2-cli

RUN docker-php-ext-install pdo pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y default-mysql-client && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://get.symfony.com/cli/installer | bash - && \
    mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]

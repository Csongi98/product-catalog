# Product Catalog (Symfony 7 + API Platform + Vue 3)

Egyszerű termékkatalógus backend (Symfony, MySQL) és frontend (Vite + Vue).

# Követelmények

Docker Desktop + Docker Compose

PHP 8.2 (CLI)

Composer

Node.js 18 (és npm)

MySQL 8.0

# Lokális indítás

1.  env beállítás (példák):

    .env

        APP_ENV=dev
        APP_SECRET=

        VITE_DEV_SERVER_URL=http://127.0.0.1:5173

        DATABASE_URL="mysql://symfony:symfony@127.0.0.1:3307/product_catalog"
        MAILER_DSN=smtp://127.0.0.1:1025
        MAILER_FROM=no-reply@product-catalog.test
        MAILER_INTERNAL=orders@product-catalog.test

        MAILER_DSN=null://null

        CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'

    .env.dev

        APP_SECRET=

        MAILER_DSN="smtp://example:example@sandbox.smtp.mailtrap.io:2525"
        MAILER_FROM="no-reply@product-catalog.test"
        MAILER_INTERNAL="orders@product-catalog.test"

    .env.test

        KERNEL_CLASS='App\Kernel'
        APP_SECRET=

        MAILER_DSN="smtp://example:example@sandbox.smtp.mailtrap.io"
        MAILER_FROM="no-reply@product-catalog.test"
        MAILER_INTERNAL="orders@product-catalog.test"

        APP_ENV=test

        //Docker esetén
        DATABASE_URL="mysql://symfony:symfony@127.0.0.1:3307/product_catalog"

        //Localhost esetén
        DATABASE_URL="mysql://root:@127.0.0.1:3306/product_catalog?serverVersion=10.4.32-MariaDB&charset=utf8mb4"

2.  Csomagok

        composer install
        npm install

3.  Migrációk

        php bin/console doctrine:migrations:migrate

        vagy

        php bin/console doctrine:schema:update --force

4.  Adatok importálása

        php bin/console app:import-products var/data/products.csv

5.  Backend indítás

        php -S 0.0.0.0:8000 -t public

        vagy

        symfony serverVersion

6.  Frontend indítás

        npm run dev

# Indítás Dockerrel

1.  Build + indítás

        docker compose build
        docker compose up

    Első indításkor a MySQL inicializál, ez eltarthat 1-2 percig.

    A PHP konténer entrypointja:

    - megvárja a DB-t,
    - lefuttatja a migrációt
    - lefuttatja az importot
    - elindítja a PHP szervert

2.  Címek:

    Backend: http://localhost:8000

    Frontend (Vite): http://localhost:5173

    Mailhog UI: http://localhost:8025

# Termékek importálása CSV-ből

A parancs: app:import-products

Forrás: src/Command/ImportProductsCommand.php

CSV alapértelmezett helye Dockerben:
/var/www/html/var/data/products.csv

CSV alapértelmezett helye localhoston:
var/data/products.csv

Futtatás:

        //Docker
        docker exec -it product_catalog_php php bin/console app:import-products /var/www/html/var/data/products.csv

        //Localhost
        php bin/console app:import-products var/data/products.csv

# Tesztelés

        php bin/phpunit

Jelenleg 3 teszt van:

-   tests/Unit/ProductEntityTest.php – entity getter/setter

-   tests/Repository/ProductListExistingTest.php – meglévő productok listázása

-   tests/Repository/ProductShowTest.php – product lekérdezése ID alapján

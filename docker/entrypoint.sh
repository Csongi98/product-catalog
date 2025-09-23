#!/bin/sh
set -e

: "${DB_HOST:=db}"
: "${DB_PORT:=3306}"
: "${DB_USER:=symfony}"
: "${DB_PASSWORD:=symfony}"
: "${IMPORT_CSV:=/var/www/html/var/data/products.csv}"

echo "Waiting for MySQL at $DB_HOST:$DB_PORT ..."
i=0
until mysqladmin ping -h"${DB_HOST}" -P"${DB_PORT}" --silent; do
  i=$((i+1))
  if [ "$i" -ge 120 ]; then
    echo "MySQL did not become ready in time. Last DB logs:"
    command -v docker >/dev/null 2>&1 && docker logs product_catalog_db | tail -n 50 || true
    exit 1
  fi
  sleep 2
done
echo "MySQL is up."

if php bin/console doctrine:migrations:migrate -n; then
  echo "Migrations done."
else
  echo "No migrations or failed; running schema:update --force ..."
  php bin/console doctrine:schema:update --force || true
fi

if [ -f "${IMPORT_CSV}" ]; then
  echo "Running import: ${IMPORT_CSV}"
  php bin/console app:import-products "${IMPORT_CSV}" || true
else
  echo "No IMPORT_CSV at ${IMPORT_CSV}, skipping import."
fi

exec "$@"

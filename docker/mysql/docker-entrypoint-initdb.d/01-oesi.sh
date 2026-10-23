#!/bin/bash
#
# Se ejecuta una sola vez, la primera que arranca el contenedor con el volumen
# vacío. La imagen de MySQL ya crea MYSQL_DATABASE y MYSQL_USER; lo que hace
# falta añadir es la intercalación —la imagen crea la base con la de por
# defecto del servidor, que no siempre es la que queremos— y los permisos para
# que Laravel pueda correr sus pruebas contra una base aparte.
set -euo pipefail

BASE="${MYSQL_DATABASE:-oesiletras}"
USUARIO="${MYSQL_USER:-oesi_user}"

mysql --protocol=socket -uroot -p"$MYSQL_ROOT_PASSWORD" <<SQL
ALTER DATABASE \`$BASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Base aparte para \`php artisan test\`: sin ella las pruebas que usan
-- RefreshDatabase tendrían que correr contra la de desarrollo y la vaciarían
-- en cada ejecución.
CREATE DATABASE IF NOT EXISTS \`${BASE}_testing\`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON \`${BASE}_testing\`.* TO '$USUARIO'@'%';

FLUSH PRIVILEGES;
SQL

echo "[oesi] Base «$BASE» lista en utf8mb4_unicode_ci, junto con «${BASE}_testing»."
echo "[oesi] El esquema lo ponen las migraciones:"
echo "[oesi]   docker compose run --rm app php artisan migrate --seed"

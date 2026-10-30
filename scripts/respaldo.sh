#!/bin/bash
# Copia de seguridad diaria: base de datos + archivos subidos desde el panel.
#
# Pensado para el cron del usuario. El volcado se hace desde el contenedor de
# MySQL y no con `php artisan db:respaldar`, porque la imagen de `app` no trae
# mysqldump y ese comando falla en este despliegue.
#
# Variables opcionales:
#   RESPALDO_DIR     dónde dejar las copias (por defecto ~/respaldos/oesiletras)
#   RESPALDO_DIAS    cuántos días conservar (por defecto 14)
#   RESPALDO_REMOTO  destino de rsync fuera del servidor, p. ej.
#                    usuario@servidor:/respaldos/oesiletras  (vacío = no se sube)
set -euo pipefail

RAIZ=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
RESPALDO_DIR="${RESPALDO_DIR:-$HOME/respaldos/oesiletras}"
RESPALDO_DIAS="${RESPALDO_DIAS:-14}"
RESPALDO_REMOTO="${RESPALDO_REMOTO:-}"
CONTENEDOR_DB="${CONTENEDOR_DB:-oesiletras_db}"

MARCA=$(date +%Y-%m-%d_%H%M%S)
umask 077   # los volcados llevan las cuentas del panel: solo el dueño los lee
mkdir -p "$RESPALDO_DIR"
chmod 700 "$RESPALDO_DIR"

echo "[$(date '+%F %T')] Inicio del respaldo"

# 1. Base de datos. Se escribe a un .tmp y se renombra al terminar, para que un
#    volcado cortado a medias nunca parezca una copia válida.
SQL="$RESPALDO_DIR/db_${MARCA}.sql.gz"
docker exec "$CONTENEDOR_DB" sh -c \
    'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysqldump -uroot --single-transaction --routines --events --triggers "$MYSQL_DATABASE"' \
    | gzip -9 > "$SQL.tmp"
# mysqldump termina con esta línea solo si llegó al final.
if ! zcat "$SQL.tmp" | tail -1 | grep -q 'Dump completed'; then
    echo "❌ El volcado de la base quedó incompleto" >&2
    rm -f "$SQL.tmp"
    exit 1
fi
mv "$SQL.tmp" "$SQL"
echo "   Base:     $SQL ($(du -h "$SQL" | cut -f1))"

# 2. Imágenes y archivos subidos desde el panel.
ARCHIVOS="$RESPALDO_DIR/storage_${MARCA}.tar.gz"
tar -czf "$ARCHIVOS" -C "$RAIZ/oesiletras/storage/app" public
echo "   Archivos: $ARCHIVOS ($(du -h "$ARCHIVOS" | cut -f1))"

# 3. Rotación.
find "$RESPALDO_DIR" -maxdepth 1 -type f \( -name 'db_*.sql.gz' -o -name 'storage_*.tar.gz' \) \
    -mtime +"$RESPALDO_DIAS" -print -delete | sed 's/^/   Borrado:  /'

# 4. Copia fuera del servidor: una copia que vive en la máquina que se puede
#    perder no es una copia.
if [ -n "$RESPALDO_REMOTO" ]; then
    rsync -a --delete "$RESPALDO_DIR/" "$RESPALDO_REMOTO/"
    echo "   Sincronizado con $RESPALDO_REMOTO"
else
    echo "⚠️  RESPALDO_REMOTO vacío: las copias solo están en este servidor"
fi

echo "[$(date '+%F %T')] Respaldo terminado"

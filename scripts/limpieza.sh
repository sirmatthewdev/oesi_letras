#!/bin/bash
# Limpieza semanal de lo que Docker y Laravel acumulan sin que nadie lo pida.
#
# - Caché de build de más de una semana (cada `docker compose build` deja capas).
# - Imágenes huérfanas que quedan al reconstruir una con el mismo nombre.
# - laravel.log: con LOG_STACK=single es un solo archivo que crece sin límite;
#   se rota a .1 y se comprime lo anterior, conservando 8 semanas.
set -uo pipefail

RAIZ=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
LOGS="$RAIZ/oesiletras/storage/logs"

echo "[$(date '+%F %T')] Limpieza semanal"

docker builder prune -f --filter until=168h | tail -1 | sed 's/^/   Build cache: /'
docker image prune -f | tail -1 | sed 's/^/   Imágenes:    /'

if [ -s "$LOGS/laravel.log" ]; then
    for i in 7 6 5 4 3 2 1; do
        [ -f "$LOGS/laravel.log.$i.gz" ] && mv "$LOGS/laravel.log.$i.gz" "$LOGS/laravel.log.$((i + 1)).gz"
    done
    rm -f "$LOGS/laravel.log.8.gz"
    # copy + truncate y no mv: PHP mantiene el archivo abierto.
    cp "$LOGS/laravel.log" "$LOGS/laravel.log.1" && : > "$LOGS/laravel.log"
    gzip -f "$LOGS/laravel.log.1"
    echo "   laravel.log rotado"
fi

echo "   Disco: $(df -h / | awk 'NR==2 {print $5" usado, "$4" libres"}')"

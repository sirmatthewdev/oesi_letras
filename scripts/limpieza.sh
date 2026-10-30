#!/bin/bash
# Limpieza semanal de lo que Docker acumula sin que nadie lo pida.
#
# - Caché de build de más de una semana (cada `docker compose build` deja capas).
# - Imágenes huérfanas que quedan al reconstruir una con el mismo nombre.
#
# Los logs ya no pasan por aquí: los de los contenedores tienen tope en el
# compose (x-logging) y Laravel escribe un archivo por día y borra los de más
# de LOG_DAILY_DAYS.
set -uo pipefail

echo "[$(date '+%F %T')] Limpieza semanal"

docker builder prune -f --filter until=168h | tail -1 | sed 's/^/   Build cache: /'
docker image prune -f | tail -1 | sed 's/^/   Imágenes:    /'

echo "   Disco: $(df -h / | awk 'NR==2 {print $5" usado, "$4" libres"}')"

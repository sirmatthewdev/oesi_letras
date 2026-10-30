#!/bin/bash
# Comprueba que el sitio y el panel respondan, y si un servicio de producción
# está caído lo vuelve a levantar.
#
# Pensado para el cron cada 5 minutos. Solo escribe cuando algo falla, para que
# el log no crezca con líneas de «todo bien».
#
# No levanta astro: es el servidor de desarrollo y se para a mano para ahorrar
# memoria. build sí: sin él, guardar en el panel no publica nada.
set -uo pipefail

RAIZ=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
URL="${URL:-http://127.0.0.1:${WEB_PORT:-8090}}"
# Las mismas capas con las que se levantó el stack en esta VM.
COMPOSE_FILES="${COMPOSE_FILES:--f docker-compose.yml -f docker-compose.dev.yml -f docker-compose.ip.yml}"
SERVICIOS="db redis app queue web build"

log() { echo "[$(date '+%F %T')] $*"; }

cd "$RAIZ"

caidos=""
for s in $SERVICIOS; do
    if [ -z "$(docker compose $COMPOSE_FILES ps -q --status running "$s" 2>/dev/null)" ]; then
        caidos="$caidos $s"
    fi
done

if [ -n "$caidos" ]; then
    log "Servicios caídos:$caidos — levantándolos"
    docker compose $COMPOSE_FILES up -d $caidos 2>&1 | sed 's/^/   /'
    sleep 15
fi

for ruta in / /login; do
    codigo=$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$URL$ruta")
    if [ "$codigo" != "200" ]; then
        log "$URL$ruta respondió $codigo"
    fi
done

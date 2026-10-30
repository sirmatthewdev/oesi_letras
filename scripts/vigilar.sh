#!/bin/bash
# Comprueba que el sitio y el panel respondan, y si un servicio de producción
# está caído lo vuelve a levantar.
#
# Pensado para el cron cada 5 minutos. Solo escribe cuando algo falla, para que
# el log no crezca con líneas de «todo bien».
#
# No levanta astro: es el servidor de desarrollo y se para a mano para ahorrar
# memoria. build sí: sin él, guardar en el panel no publica nada.
#
# Las capas de Compose salen de COMPOSE_FILE en el .env de la raíz, y la URL de
# OESI_SITE: así sigue funcionando igual en modo IP y con el dominio, sin tocar
# el crontab al pasar de uno a otro (scripts/pasar-a-dominio.sh).
set -uo pipefail

RAIZ=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
SERVICIOS="db redis app queue web build"

log() { echo "[$(date '+%F %T')] $*"; }

# Lee una sola clave del .env, sin cargarlo entero en el entorno.
valor() { grep -E "^$1=" "$RAIZ/.env" | tail -1 | cut -d= -f2-; }

cd "$RAIZ"

caidos=""
for s in $SERVICIOS; do
    if [ -z "$(docker compose ps -q --status running "$s" 2>/dev/null)" ]; then
        caidos="$caidos $s"
    fi
done

if [ -n "$caidos" ]; then
    log "Servicios caídos:$caidos — levantándolos"
    docker compose up -d --no-deps $caidos 2>&1 | sed 's/^/   /'
    sleep 15
fi

# Con dominio y TLS se pide por HTTPS al propio servidor (--resolve): así se
# comprueba también el certificado, sin depender de que el DNS responda.
SITIO=$(valor OESI_SITE)
if [[ "$SITIO" == https://* ]]; then
    dominio=${SITIO#https://}
    puerto=$(valor WEB_SSL_PORT)
    puerto=${puerto:-443}
    resolver=(--resolve "$dominio:$puerto:127.0.0.1")
    base="https://$dominio:$puerto"
else
    base="http://127.0.0.1:$(valor WEB_PORT)"
    resolver=()
fi

for ruta in / /login; do
    codigo=$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "${resolver[@]}" "$base$ruta")
    if [ "$codigo" != "200" ]; then
        log "$base$ruta respondió $codigo"
    fi
done

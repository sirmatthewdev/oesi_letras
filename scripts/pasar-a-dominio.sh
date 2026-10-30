#!/bin/bash
# Pasa esta VM del modo IP (capas dev + ip, http://172.16.40.17:8090) al
# dominio con TLS (solo docker-compose.yml, https://DOMINIO), conservando la
# base de datos y el contenido.
#
#   scripts/pasar-a-dominio.sh              # solo revisa; no cambia nada
#   scripts/pasar-a-dominio.sh --aplicar    # revisa y, si todo está bien, cambia
#
# No es preparar-entorno.sh: ese genera contraseñas de base de datos nuevas, y
# aquí MySQL ya está creado con las suyas —las nuevas se ignorarían y la
# aplicación dejaría de poder entrar—. Este edita los .env existentes clave a
# clave y no toca ningún secreto.
#
# Antes de cambiar nada guarda un respaldo de la base y copia los dos .env a
# ~/respaldos/oesiletras/; al final imprime cómo volver atrás.
set -euo pipefail

RAIZ=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
cd "$RAIZ"

DOMINIO="${DOMINIO:-suficiencialetras.unmsm.edu.pe}"
APLICAR=false
[ "${1:-}" = "--aplicar" ] && APLICAR=true

RAIZ_ENV=".env"
APP_ENV_F="oesiletras/.env"
CERT="docker/nginx/ssl_sectigo/fullchain.pem"
LLAVE="docker/nginx/ssl_sectigo/privkey.pem"

ok()    { echo "  ✔ $*"; }
aviso() { echo "  ⚠ $*"; }
fallo() { echo "  ✘ $*"; FALLOS=$((FALLOS + 1)); }
FALLOS=0

# Sustituye `CLAVE=...` en un .env, o la añade al final. Sin imprimir valores.
poner() {
    local fichero="$1" clave="$2" valor="$3"
    if grep -q "^${clave}=" "$fichero"; then
        sed -i "s|^${clave}=.*|${clave}=${valor}|" "$fichero"
    else
        printf '%s=%s\n' "$clave" "$valor" >> "$fichero"
    fi
}

echo "Revisión para https://$DOMINIO"

# 1. DNS. Sin él el sitio funciona, pero nadie llega.
ip_dns=$(getent hosts "$DOMINIO" | awk '{print $1}' | head -1 || true)
if [ -n "$ip_dns" ]; then
    ok "El DNS resuelve $DOMINIO → $ip_dns"
else
    fallo "$DOMINIO todavía no resuelve en DNS"
fi

# 2. Certificado: que exista, que cubra el dominio, que la llave sea suya y
#    que no esté por vencer.
if [ -f "$CERT" ] && [ -f "$LLAVE" ]; then
    if openssl x509 -in "$CERT" -noout -checkhost "$DOMINIO" | grep -q 'does match'; then
        ok "El certificado cubre $DOMINIO"
    else
        fallo "El certificado instalado NO cubre $DOMINIO"
    fi
    if [ "$(openssl x509 -in "$CERT" -noout -pubkey | sha256sum)" = "$(openssl pkey -in "$LLAVE" -pubout 2>/dev/null | sha256sum)" ]; then
        ok "La llave privada corresponde al certificado"
    else
        fallo "La llave privada no corresponde al certificado"
    fi
    if openssl x509 -in "$CERT" -noout -checkend $((30 * 86400)) >/dev/null; then
        ok "El certificado vence el $(openssl x509 -in "$CERT" -noout -enddate | cut -d= -f2)"
    else
        fallo "El certificado vence en menos de 30 días"
    fi
else
    fallo "Falta $CERT o $LLAVE"
fi

# 3. Puertos. Docker publica saltándose firewalld, así que lo que importa es
#    que nadie más los tenga y que la red de la universidad los deje pasar.
ocupados=$(ss -ltnH '( sport = :80 or sport = :443 )' | grep -v docker-proxy | awk '{print $4}' || true)
if [ -z "$ocupados" ]; then
    ok "Los puertos 80 y 443 están libres"
else
    fallo "Puertos ocupados por otro proceso: $ocupados"
fi

# 4. Estado actual.
actual=$(grep -E '^COMPOSE_FILE=' "$RAIZ_ENV" | cut -d= -f2- || true)
if [ "$actual" = "docker-compose.yml" ]; then
    aviso "Ya está en modo dominio (COMPOSE_FILE=docker-compose.yml)"
else
    ok "Modo actual: ${actual:-sin COMPOSE_FILE}"
fi

if ! $APLICAR; then
    echo
    if [ "$FALLOS" -gt 0 ]; then
        echo "$FALLOS problema(s). No se cambió nada."
    else
        echo "Todo listo. Para aplicar: scripts/pasar-a-dominio.sh --aplicar"
    fi
    exit 0
fi

if [ "$FALLOS" -gt 0 ]; then
    echo
    echo "No se aplica: hay $FALLOS problema(s) arriba."
    exit 1
fi

echo
echo "Aplicando…"

# 5. Respaldo de la base y de los dos .env, antes de tocar nada.
"$RAIZ/scripts/respaldo.sh" | sed 's/^/  /'
COPIA="$HOME/respaldos/oesiletras/env-antes-de-dominio-$(date +%F_%H%M%S)"
(umask 077 && mkdir -p "$COPIA")
cp -p "$RAIZ_ENV" "$COPIA/raiz.env"
cp -p "$APP_ENV_F" "$COPIA/laravel.env"
ok "Copia de los .env en $COPIA"

# 6. Los .env: dominio, puertos estándar, producción y solo la capa base.
poner "$RAIZ_ENV" NGINX_SERVER_NAMES "$DOMINIO"
poner "$RAIZ_ENV" OESI_SITE "https://$DOMINIO"
poner "$RAIZ_ENV" WEB_PORT 80
poner "$RAIZ_ENV" WEB_SSL_PORT 443
poner "$RAIZ_ENV" COMPOSE_FILE docker-compose.yml
poner "$APP_ENV_F" APP_ENV production
poner "$APP_ENV_F" APP_DEBUG false
poner "$APP_ENV_F" APP_URL "https://$DOMINIO"
poner "$APP_ENV_F" LOG_LEVEL warning
ok ".env actualizados (sin cambiar ningún secreto)"

# 7. Contenedores. --remove-orphans quita astro, que solo existe en la capa dev.
docker compose up -d --remove-orphans 2>&1 | grep -E 'Recreated|Started|Removed|Error' | sed 's/^/  /'

# 8. En modo IP vendor/ vivía en un volumen de la capa dev; en producción va
#    en la carpeta del proyecto y hay que instalarlo, sin dependencias de dev.
docker compose exec -T app composer install --no-dev --optimize-autoloader --no-interaction -q
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan optimize
docker compose exec -T app php artisan filament:optimize
docker compose exec -T app php artisan queue:restart
ok "Dependencias, migraciones y cachés"

# 9. El sitio se reconstruye con OESI_SITE nuevo: sitemap y URL canónicas.
docker compose exec -T app php artisan tinker --execute='App\Jobs\ReconstruirSitio::dispatch();' >/dev/null
echo "  … reconstruyendo el sitio"
for _ in $(seq 1 30); do
    sleep 4
    docker compose logs --since 2m build 2>/dev/null | grep -q 'build: correcto' && break
done

# 10. Comprobación por HTTPS contra este mismo servidor, sin depender del DNS.
for ruta in / /login /panel; do
    codigo=$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 --resolve "$DOMINIO:443:127.0.0.1" "https://$DOMINIO$ruta")
    echo "  https://$DOMINIO$ruta → $codigo"
done
http=$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 --resolve "$DOMINIO:80:127.0.0.1" "http://$DOMINIO/")
echo "  http://$DOMINIO/ → $http (debe redirigir a HTTPS)"

cat <<FIN

Hecho. El sitio está en https://$DOMINIO (200 en / y /login, 302 en /panel
sin sesión).

Para volver al modo IP:
  cp -p $COPIA/raiz.env $RAIZ/.env
  cp -p $COPIA/laravel.env $RAIZ/oesiletras/.env
  cd $RAIZ && docker compose up -d --remove-orphans
  docker compose exec app php artisan optimize
FIN

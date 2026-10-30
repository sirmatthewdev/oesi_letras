#!/bin/bash
# Genera los dos .env de PRODUCCIÓN de la OESI, con los secretos ya coincidentes.
#
#   bash scripts/preparar-entorno.sh                     # dominio por defecto
#   bash scripts/preparar-entorno.sh otro.unmsm.edu.pe   # otro dominio
#
# Se ejecuta UNA vez, en la VM, desde la raíz del repositorio y antes del paso 4
# de docs/produccion.md. Existe porque el fallo más repetido de esta instalación
# es copiar a mano los mismos secretos en dos ficheros que leen programas
# distintos: si DB_PASSWORD, OESI_BUILD_TOKEN u OESI_VISTA_PREVIA_TOKEN no
# coinciden, MySQL no deja entrar, publicar no reconstruye el sitio o la vista
# previa se rechaza, y ninguno de los tres da un error que lo diga.
#
# No pisa nada: si alguno de los dos .env ya existe, se detiene sin tocarlos.
# Lo único que deja por rellenar son las credenciales SMTP (MAIL_*), que no se
# pueden inventar.
set -euo pipefail

RAIZ=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
cd "$RAIZ"

DOMINIO="${1:-suficiencialetras.unmsm.edu.pe}"
RAIZ_ENV=".env"
APP_ENV_F="oesiletras/.env"

for f in "$RAIZ_ENV" "$APP_ENV_F"; do
    if [ -e "$f" ]; then
        echo "Ya existe $f: no se toca nada. Bórralo a mano si de verdad quieres regenerarlo." >&2
        exit 1
    fi
done

# Secretos sin caracteres que haya que escapar en un .env ni en sed.
secreto() {
    head -c 64 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c "${1:-40}"
}

DB_PASSWORD=$(secreto 32)
DB_ROOT_PASSWORD=$(secreto 32)
BUILD_TOKEN=$(secreto 48)
VISTA_PREVIA_TOKEN=$(secreto 48)
ADMIN_PASSWORD=$(secreto 20)

# Sustituye `CLAVE=...` en un fichero, o la añade al final si no está.
poner() {
    local fichero="$1" clave="$2" valor="$3"
    if grep -q "^${clave}=" "$fichero"; then
        sed -i "s|^${clave}=.*|${clave}=${valor}|" "$fichero"
    else
        printf '%s=%s\n' "$clave" "$valor" >> "$fichero"
    fi
}

cp .env.example "$RAIZ_ENV"
poner "$RAIZ_ENV" DB_PASSWORD "$DB_PASSWORD"
poner "$RAIZ_ENV" DB_ROOT_PASSWORD "$DB_ROOT_PASSWORD"
poner "$RAIZ_ENV" UID "$(id -u)"
poner "$RAIZ_ENV" GID "$(id -g)"
poner "$RAIZ_ENV" NGINX_SERVER_NAMES "$DOMINIO"
poner "$RAIZ_ENV" OESI_SITE "https://$DOMINIO"
poner "$RAIZ_ENV" OESI_BUILD_TOKEN "$BUILD_TOKEN"
poner "$RAIZ_ENV" OESI_VISTA_PREVIA_TOKEN "$VISTA_PREVIA_TOKEN"
# VM propia: los puertos estándar.
poner "$RAIZ_ENV" WEB_PORT 80
poner "$RAIZ_ENV" WEB_SSL_PORT 443

cp oesiletras/.env.docker "$APP_ENV_F"
poner "$APP_ENV_F" APP_ENV production
poner "$APP_ENV_F" APP_DEBUG false
poner "$APP_ENV_F" APP_URL "https://$DOMINIO"
poner "$APP_ENV_F" LOG_LEVEL warning
poner "$APP_ENV_F" DB_PASSWORD "$DB_PASSWORD"
poner "$APP_ENV_F" OESI_BUILD_TOKEN "$BUILD_TOKEN"
poner "$APP_ENV_F" OESI_VISTA_PREVIA_TOKEN "$VISTA_PREVIA_TOKEN"
poner "$APP_ENV_F" ADMIN_PASSWORD "$ADMIN_PASSWORD"

# Ningún otro usuario de la máquina tiene por qué leer contraseñas.
chmod 600 "$RAIZ_ENV" "$APP_ENV_F"

cat <<FIN
Listo: $RAIZ_ENV y $APP_ENV_F, para https://$DOMINIO, con UID $(id -u) y GID $(id -g).

Contraseña del administrador (examensuficiencia.flch@unmsm.edu.pe):

    $ADMIN_PASSWORD

Anótala ahora. También queda en $APP_ENV_F (ADMIN_PASSWORD) hasta que la
quites: bórrala de ahí después de entrar al panel por primera vez.

Falta rellenar a mano, en $APP_ENV_F, el correo de salida:
    MAIL_MAILER=smtp  MAIL_HOST  MAIL_PORT  MAIL_SCHEME  MAIL_USERNAME  MAIL_PASSWORD
(ver oesiletras/docs/correo.md). Sin eso el panel funciona, pero no llega el
enlace de «olvidé mi contraseña».

Siguiente: el certificado (paso 3 de docs/produccion.md).
FIN

# OESI Letras — Laravel 12 + Astro, con Docker

Sitio de la **Oficina de Examen de Suficiencia en Idiomas (OESI)** de la
Facultad de Letras y Ciencias Humanas de la UNMSM: requisitos, modalidades,
cronograma, pagos, formularios y preguntas frecuentes del examen de suficiencia.

Sustituye a la landing que la oficina tenía publicada en
`letras.unmsm.edu.pe/oficina-de-examen-de-suficiencia-en-idiomas/`. Su
contenido está migrado entero, y ahora se edita desde un panel en vez de
editando HTML.

> **Estado: listo para producción.** La oficina validó el contenido, las cifras
> y los enlaces de San Market el 20-10-2026: entra **publicado**. Va en su propia
> VM, en `https://suficiencialetras.unmsm.edu.pe`. Lo que queda está en
> [Pendiente](#pendiente).

El sitio son **dos piezas**:

| | Qué es | Dónde vive |
|---|---|---|
| **Laravel** | El panel (Filament) y la API de contenido | `oesiletras/` |
| **Astro** | El sitio público, ficheros estáticos | `sitio/` |

Astro se construye contra la API y Nginx entrega el resultado. Laravel solo
responde HTML en `/panel`, `/login`, `/profile` y `/gestion` (vista previa).
Guardar en el panel encola un trabajo que pide reconstruir el sitio, así que
un cambio de contenido no exige tocar nada a mano.

**De dónde sale.** El proyecto es una copia del sitio del CERSEU
(`CERSEU/CerseuLetras`), recortada a la OESI: se retiró la oferta de cursos, la
plana docente, la admisión, las solicitudes y el buscador, y se conservó la
infraestructura (Docker, panel, API, reconstrucción, vista previa). Son
proyectos **independientes**: base de datos, contenedores, dominio y
administradores propios. Un cambio en uno no afecta al otro.

**Si lo que vas a montar es el servidor**, ver
[docs/produccion.md](docs/produccion.md): de una VM vacía a un sitio publicado
con TLS. Lo de abajo es la instalación de desarrollo.

## Estructura

```
├── docker-compose.yml       ← producción (nginx con TLS)
├── docker-compose.dev.yml   ← capa de desarrollo (sin TLS, Vite, Astro dev)
├── .env.example             ← variables de Compose: BD, puertos, dominio
├── docker/                  ← PHP, MySQL y Nginx
├── docs/produccion.md       ← de una VM vacía al sitio publicado
├── scripts/preparar-entorno.sh  ← genera los dos .env de producción
├── scripts/check_ssl_expiry.sh   ← días que le quedan al certificado
├── oesiletras/              ← Laravel 12
│   ├── app/Models/OesiSetting.php            ← el contenido entero, una fila
│   ├── app/Filament/Pages/OficinaDeSuficiencia.php ← el panel: «Contenido del sitio»
│   ├── app/Http/Controllers/Api/             ← /api/v1/sitio, /menu, /oesi
│   ├── database/seeders/data/oesi.json       ← contenido migrado de la landing
│   └── tests/Feature/OesiTest.php            ← la migración, pieza a pieza
└── sitio/                   ← Astro
    ├── src/pages/                            ← inicio y una página por tema, o «próximamente»
    ├── src/components/oesi/                  ← páginas, etapas, tarjetas, imágenes, copiar
    ├── src/lib/oesi.ts                       ← soles y **negritas**
    └── e2e/                                  ← Playwright
```

## Las páginas

El contenido se reparte por lo que viene a hacer quien llega, no por el orden
del documento de la oficina:

| Ruta | Qué tiene |
|---|---|
| `/` | Hero con el contacto y las cifras, **cómo inscribirte** (fecha, paso 1, paso 2) con el requisito, accesos a las páginas de consulta y el curso del CEID como recurso opcional |
| `/examen` | Qué es, a quién va, **el requisito**, las habilidades evaluadas y, al final, escala, resultados y vigencia del certificado |
| `/modalidades` | Las indicaciones para el día del examen, presencial y virtual |
| `/cronograma` | Antes de inscribirse: la publicación del mes y el aviso de fechas |
| `/inscripcion` | Paso 1, el pago; paso 2, el formulario: el de la convocatoria y, aparte, los de otros trámites |
| `/preguntas` | Las preguntas frecuentes por tema, con buscador |

- **Numeración de la oficina**: pago = paso 1 y formulario = paso 2, como en
  sus imágenes. Elegir la fecha va antes, sin número. Un indicador de etapas
  común une /cronograma e /inscripcion.
- **Las anclas de la landing** (`/#inscripcion`, `/#preguntas`, `/#faq-…`)
  llevan a su página: siguen circulando en mensajes y publicaciones.
- **El menú** apunta a las páginas por nombre (`route_name` →
  `App\Support\DestinosPublicos`), así que el menú guardado se actualizó sin
  tocarlo. Marca la página actual con `aria-current`.
- Cada página tiene un solo `h1`, su `<title>` y su descripción. El esquema
  `FAQPage` solo va en /preguntas.

## Cómo se actualiza el contenido

Todo en el panel → **Contenido del sitio**, una pestaña por sección.

| Qué | Pestaña | Cada cuánto |
|---|---|---|
| **Imagen del cronograma** y su descripción | Cronograma | cada mes |
| Formularios de la convocatoria (pregrado / posgrado) y para quién es cada uno | Cronograma | si cambian |
| **Enlace de San Market** de un concepto | Tarifario | cuando San Market cambie el código |
| Costos de inscripción | Inscripción | cuando cambien |
| Formularios de otros trámites (posgrado, bachilleres, duplicado) y cuándo se usa cada uno | Inscripción | si cambian |
| Preguntas frecuentes | Preguntas | libre |
| Correo, WhatsApp, horario, redes | Contacto | — |
| Logotipo, favicon, descripción | Sitio | — |
| Publicar / volver a borrador | Publicación | — |
| Mostrar las cifras de la cabecera | Publicación → «verificadas» | tras confirmarlas |
| Menú de la cabecera | Menú | — |

- Cada enlace y cada precio se escriben **una vez**; la página los repite donde
  haga falta. El enlace de WhatsApp se compone con número y mensaje.
- Importes sin símbolo (`300`); la página los pinta `S/ 300.00`.
- Negrita: `**así**`. No se admite HTML.
- Las imágenes son URL de `letras.unmsm.edu.pe`, como en la landing. Si una no
  carga, la página muestra un respaldo con «Abrir la imagen» y WhatsApp. El
  build no depende de esa web.
- **Vista previa**: botón «Vista previa» arriba a la derecha de la página del
  panel. Enseña lo **guardado**, borrador incluido: hay que guardar antes.
- Al guardar, el sitio se reconstruye solo (unos minutos).

## Publicar y retirar

El contenido entra publicado. Para retirarlo —una revisión, un cambio grande—:
Panel → Contenido del sitio → Publicación → **Borrador** → Guardar. La portada
pasa a decir «próximamente» con el correo de contacto; el contenido no se
pierde y se sigue viendo en la vista previa. Volver a «Publicada» lo restituye.

Las cifras tienen su propio interruptor en la misma pestaña: desmarcar
«verificadas» las retira sin despublicar la página.

## Pendiente

**De mantenimiento**, en el panel, sin bloquear la puesta en marcha:

- **Cronograma**: la imagen sembrada es la del 21–28 de setiembre de 2026.
  Subir la del mes en Contenido del sitio → Cronograma.
- **Logotipo**: no hay uno de la oficina. Mientras no se suba, la cabecera usa
  el de la Facultad y el favicon es la sigla «OESI» sobre el azul institucional.

**Del despliegue** (ver [docs/produccion.md](docs/produccion.md)):

- El **DNS** de `suficiencialetras.unmsm.edu.pe` hacia la VM. Mientras tanto
  el sitio va por IP; cuando resuelva, `scripts/pasar-a-dominio.sh --aplicar`
  (ver «Pasar de la IP al dominio» en docs/produccion.md). El certificado
  `*.unmsm.edu.pe` de `docker/nginx/ssl_sectigo/` ya lo cubre.
- Las **credenciales SMTP** del buzón de la oficina (`MAIL_*`). Sin ellas no
  llega el enlace de «olvidé mi contraseña».

**Validado por la oficina el 20-10-2026**, y publicado tal cual: el contenido
migrado de la landing, las cifras de la cabecera (2 500+ exámenes, 15 idiomas,
98 % de satisfacción, 12 años), los enlaces de San Market y el dominio.

Recursos comprobados: las 5 imágenes, la página del curso CEID, San Market,
Facebook, Instagram, el canal de WhatsApp y wa.me responden 200. Tres
formularios responden 401 porque Google pide iniciar sesión, lo normal cuando
aceptan archivos.

## Instalación de desarrollo

Requisitos: Git y Docker Desktop (o Docker Engine) con Compose v2.

### 0. Clonar

```bash
git clone https://github.com/sirmatthewdev/oesi_letras.git
cd oesi_letras
```

Todo lo que sigue se ejecuta desde esa carpeta, la raíz del repositorio.

### 1. Los dos `.env`

| Fichero | Lo lee | Para qué |
|---|---|---|
| `.env` (raíz) | Docker Compose | Base de datos, puertos, dominio, tokens |
| `oesiletras/.env` | Laravel | Conexión, correo, tokens |

```bash
cp .env.example .env
cp oesiletras/.env.docker oesiletras/.env
```

Las dos plantillas ya traen los mismos `DB_DATABASE`, `DB_USERNAME`,
`DB_PASSWORD`, `OESI_BUILD_TOKEN` y `OESI_VISTA_PREVIA_TOKEN`: si cambias uno,
cámbialo en los dos. En `oesiletras/.env`, para desarrollo:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8090   # el WEB_PORT de la raíz
```

Con `APP_ENV=production`, `migrate` pide confirmación por teclado y en
`docker compose exec` se cancela solo, dejando la base vacía.

**Si en la misma máquina corre el CERSEU**, en el `.env` de la raíz pon
`WEB_PORT` y `WEB_SSL_PORT` libres (p. ej. `8090` y `8453`), y
`NGINX_SERVER_NAMES=localhost 127.0.0.1`. Los demás puertos ya vienen apartados
de los del CERSEU:

| | CERSEU | OESI |
|---|---|---|
| Nginx | 80 / 443 | `WEB_PORT` / `WEB_SSL_PORT` |
| MySQL (host) | 3307 | 3308 |
| Vite | 5173 | 5174 |
| Astro dev | 4321 | 4331 |
| Servicio de build | 4322 | 4332 |

Los contenedores se llaman `oesiletras_*` y los volúmenes cuelgan del proyecto
`oesiletras`: no se pisan con los del CERSEU.

### 2. Levantar e instalar

```bash
C="docker compose -f docker-compose.yml -f docker-compose.dev.yml"
$C up -d --build
$C exec app composer install
$C exec app php artisan key:generate
$C exec app php artisan migrate --seed
$C exec app php artisan storage:link
$C exec app sh -c "npm install && npm run build"     # assets del login y el perfil
$C exec build npm run build                          # el sitio público, a sitio/dist
```

El trabajador de colas se reinicia en bucle hasta que existe `vendor/`: es
normal antes del `composer install`. El servicio `build` hace su propio
`npm install` al arrancar; en la primera instalación tarda un par de minutos,
y si el último comando falla por módulos que faltan, espera a que termine
(`$C logs -f build` dice cuándo arranca su servidor) y repítelo.

Al terminar, el sitio publicado tiene seis páginas: `/`, `/examen`,
`/modalidades`, `/cronograma`, `/inscripcion` y `/preguntas`.

`--seed` crea el administrador (`examensuficiencia.flch@unmsm.edu.pe`, o
`ADMIN_EMAIL`) con la contraseña de `ADMIN_PASSWORD`; si está vacía, genera una
y **la imprime una sola vez**. Siembra también el menú y el contenido de la
oficina, publicado.

**En Windows** `vendor/` y `node_modules/` viven en volúmenes y no en la
carpeta: sobre el bind mount de Docker Desktop `readdir()` trunca los
directorios grandes y la mitad de los iconos dejan de existir. Por eso
`composer` y `npm` se ejecutan dentro del contenedor.

### 3. Direcciones

| | Dirección |
|---|---|
| Sitio | `http://localhost:${WEB_PORT}` |
| Panel | `http://localhost:${WEB_PORT}/panel` |
| API | `http://localhost:${WEB_PORT}/api/v1/oesi` |
| Astro con recarga | `http://localhost:4331` |

## Vista previa

El panel la pide al servicio `build`, que construye el sitio con el borrador en
`sitio/dist-vista-previa/` (nunca en `dist/`, que es lo que publica Nginx) y la
sirve Laravel detrás de la sesión, en `/gestion/vista-previa`.

Necesita `OESI_VISTA_PREVIA_TOKEN` igual en los dos `.env`. Sin él, la vista
previa está desactivada y la pantalla lo dice.

Un `npm run build` lanzado a mano construye siempre el sitio **público**: la
plantilla solo lee el borrador de `OESI_RENDER_VISTA_PREVIA`, que pone el
servicio de build en el render de vista previa y nadie más.

## Pruebas

```bash
C="docker compose -f docker-compose.yml -f docker-compose.dev.yml"
$C exec app php artisan test                       # PHPUnit
$C exec astro npx vitest run                       # unitarias del sitio
$C --profile e2e run --rm e2e                      # Playwright, contra sitio/dist
```

Las de Playwright van contra lo **publicado**. Si la página se devuelve a
borrador, las de las páginas se omiten solas y la de la portada comprueba el
«próximamente»; para probarla entonces, contra la vista previa:

```bash
$C --profile e2e run --rm -e OESI_E2E=http://localhost:4400 e2e sh -c \
  "(cd dist-vista-previa && python3 -m http.server 4400 &) ; sleep 1; npx playwright test"
```

(Ese servidor de Python no sirve la página 404, así que la prueba del h1 de la
404 falla solo ahí.)

## Diferencias con la landing original

- Tipografía y dorado del sitio (Playfair/Inter, `#B6A350`) en vez de Libre
  Baskerville/DM Sans y `#A88F1D`; el azul `#143B63` es el mismo.
- Sin GSAP propio: se usan el contador y la capa de animación del sitio. Todo
  respeta `prefers-reduced-motion`.
- **Sin pestañas**: la landing era una página con siete pestañas; aquí son
  páginas (ver [Las páginas](#las-páginas)), y la única navegación es el menú
  de la cabecera.
- La barra de progreso es el anillo del botón «Volver arriba».
- Preguntas frecuentes en `<details>`: sin script, con teclado y encontrables
  con el buscador del navegador.
- Tablas de costos y tarifario apiladas en móvil.
- **Defectos corregidos**: `whatsappVisible` sin declarar (se deriva del
  número), etiquetas mal anidadas en el contacto, copia de un dato inexistente y
  `range.selectNodecontents` (método que no existe), respaldo de imagen en las
  cinco imágenes incluso si fallan antes de cargar el script, ancla respetada al
  recargar y desde el menú de la cabecera, y el WhatsApp del curso compuesto desde su campo.
- **Textos nuevos**, solo de interfaz: el aviso de fechas del cronograma
  (editable), la nota de que el curso es opcional, que los botones llevan a San
  Market y la página no procesa pagos, «¿Otra duda?» al final de las preguntas,
  el aviso de cifras sin verificar (solo en la vista previa) y la portada de
  «próximamente».

## Limitaciones

- **Un solo rol** (`admin`): quien entra al panel lo edita todo. Es un sitio de
  una sola oficina, así que no pesa como pesaba compartido con el CERSEU.
- Las imágenes siguen alojadas en `letras.unmsm.edu.pe`. Subirlas al propio
  panel sería un campo de subida por imagen; no se ha hecho para no cambiar las
  URL públicas sin decidirlo.
- El código conserva comentarios que cuentan por qué se decidió algo en el
  CERSEU, de donde viene. Se han reescrito los que describían comportamiento que
  ya no existe; los que explican una decisión que sigue en pie se dejan.

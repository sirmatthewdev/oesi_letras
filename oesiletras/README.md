# oesiletras — panel y API

La mitad Laravel del sitio de la OESI: el panel de administración (Filament, en
`/panel`) y la API de solo lectura (`/api/v1`) contra la que se construye el
sitio público en Astro. La instalación está en el [README de la raíz](../README.md).

## Datos

| Tabla | Qué guarda |
|---|---|
| `oesi_settings` | **Todo el contenido**, en una fila: identidad, contacto, redes, cifras, curso, examen, escala, vigencia, resultados, requisitos, habilidades, modalidades, cronograma, inscripción, costos, tarifario, formularios y preguntas. Las listas en JSON. |
| `menu_items` | El menú de la cabecera, con destinos internos por nombre (`App\Support\DestinosPublicos`) o URL externa |
| `users` | Administradores (`role = admin`, `is_active`) |
| `cache`, `jobs`, `sessions`… | Las del framework |

`estado` (`borrador` / `publicado`) decide si la API sirve la página, y
`estadisticas_verificadas` si sirve las cifras de la cabecera.

### Dónde no debe vivir el contenido

El contenido institucional no se escribe en una migración, ni en un valor por
defecto de un controlador, ni en una plantilla. Las migraciones crean
estructura; el contenido migrado de la landing está en
`database/seeders/data/oesi.json`, lo carga `OesiSeeder` **una vez** —no pisa
una fila que ya tenga contenido— y a partir de ahí se edita en el panel.

`InstalacionLimpiaTest` lo vigila en las dos direcciones: que una instalación
nueva trae la oficina entera, y que no trae nada del CERSEU, de donde sale este
proyecto.

## API

| Ruta | Devuelve |
|---|---|
| `GET /api/v1/sitio` | Identidad para la cabecera y el pie. Siempre, aunque la página esté en borrador. |
| `GET /api/v1/menu` | El menú, anidado y con las rutas resueltas (`/#inscripcion`…) |
| `GET /api/v1/oesi` | La página. `data: null` en borrador, salvo con la cabecera de vista previa |

Los importes salen como números y las listas siempre como listas (las filas
del repetidor de Filament pueden llegar con claves UUID; la API las normaliza).
El enlace de WhatsApp lo compone `OesiSetting::whatsapp()` a partir del número
y el mensaje: no se guarda.

**Vista previa**: una petición con `X-Vista-Previa: <OESI_VISTA_PREVIA_TOKEN>`
ve el borrador y su respuesta va `no-store`. Sin token configurado, la vista
previa está cerrada pase lo que pase (`App\Support\VistaPrevia`).

## Panel

- **Contenido del sitio** (`App\Filament\Pages\OficinaDeSuficiencia`): una
  página, no un recurso, porque hay una sola fila. Una pestaña por tema del
  sitio público y su botón de **Vista previa**, que sirve todas las páginas
  del render con sus enlaces reescritos para no salir de él.
- **Menú** y **Usuarios**: recursos.

Guardar `OesiSetting` o `MenuItem` encola `ReconstruirSitio` (ver
`PublicacionObserver`), que pide al servicio `build` un `astro build`. El
trabajo es único y con retraso: seis guardados seguidos son una sola
reconstrucción.

## Pruebas

```bash
php artisan test            # dentro del contenedor `app`
```

`OesiTest` compara la migración con la fuente pieza a pieza —precios, códigos
de San Market, formularios, escala— y comprueba que cada campo del panel es una
columna y que cada clave del fichero de datos tiene la suya.

Más documentación: [docs/correo.md](docs/correo.md) (SMTP y recuperación de
contraseña).

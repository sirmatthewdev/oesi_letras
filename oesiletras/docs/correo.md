# Correo: qué falta para que funcione

**No hay credenciales en el repositorio**: este documento solo explica qué hay
que rellenar y dónde.

## Estado actual

`MAIL_MAILER=log`, tanto en `.env` como en `.env.example`. Con ese valor Laravel
**no envía nada**: escribe el mensaje completo en `storage/logs/laravel.log` y
devuelve éxito. Por eso nada falla de forma visible — y por eso tampoco llega
nada.

Comprobado: `Mail::raw(...)` no lanza excepción y el remitente configurado es
`hello@example.com` con nombre `Laravel`, los valores que trae Laravel de
fábrica.

## Qué depende de esto

**Recuperar la contraseña del panel.** Las rutas existen y funcionan
(`GET/POST forgot-password`, `GET/POST reset-password`), y `User` usa
`Notifiable`, así que el mecanismo está completo. Lo único que falta es el
transporte: sin él, si alguien de la oficina olvida su contraseña, el enlace de
recuperación acaba en un fichero de log del servidor, y hace falta que alguien
con acceso por SSH lo saque de ahí o la restablezca por consola.

El sitio público no envía correo: no tiene formularios propios, los de
inscripción son de Google Forms.

## Qué hay que hacer

En el `.env` de **producción** (no en el repositorio):

```
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=examensuficiencia.flch@unmsm.edu.pe
MAIL_PASSWORD=<contraseña de aplicación>
```

Todo eso **ya está puesto salvo la contraseña**.

### Gmail no acepta la contraseña de la cuenta

Desde 2022 Google no permite autenticar en SMTP con la contraseña normal. Hace
falta una **contraseña de aplicación**: 16 caracteres, generada aparte, y que
exige tener activada la verificación en dos pasos en la cuenta.

Si se usa la contraseña de la cuenta, Gmail responde:

```
535-5.7.8 Username and Password not accepted
```

Cómo generarla: entrar en la cuenta → *Seguridad* → activar *Verificación en 2
pasos* → *Contraseñas de aplicaciones* → crear una para «Correo». Se copia sin
espacios en `MAIL_PASSWORD`.

`MAIL_SCHEME` va en `smtp` (puerto 587, STARTTLS). Con `tls` Laravel 12 falla:
*«The "tls" scheme is not supported»*. Para el puerto 465 sería `smtps`.

### Si las contraseñas de aplicación «no están disponibles»

**Usar la contraseña de la cuenta no es una alternativa.** Google la rechaza en
SMTP desde mayo de 2022; no es configurable desde aquí.

Antes de descartarlas, comprobar lo más habitual: **la opción solo aparece si la
cuenta tiene la verificación en dos pasos activada**. Sin 2FA no está en el
menú, y es fácil concluir que el dominio la bloquea cuando no es así.

Si con 2FA activa sigue sin aparecer, la bloquea el administrador de Workspace
de `unmsm.edu.pe`. Opciones, de mejor a peor:

**1. Relé SMTP de Google** — `smtp-relay.gmail.com`, puerto 587. El
administrador autoriza la **IP del servidor** en *Apps → Gmail → Enrutamiento →
Servicio de relé SMTP*. Autentica por IP, así que **no hace falta contraseña**:

```
MAIL_HOST=smtp-relay.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
```

Es lo indicado para una cuenta institucional: nadie tiene que custodiar una
credencial y no se rompe si alguien cambia la contraseña del buzón.

**2. Servidor de correo propio de la universidad.** Conviene preguntarlo antes
que nada: si el área de sistemas facilita un `mail.unmsm.edu.pe` o similar,
funciona con usuario y contraseña normales — la restricción es de Google, no del
protocolo. Sería lo más rápido.

**3. Que el administrador habilite las contraseñas de aplicación** para esa
cuenta.

**4. Servicio transaccional** (Brevo, Mailgun, Resend). Solo cambia el bloque
`MAIL_*`, el código no se toca. Pero exige que el administrador añada **SPF y
DKIM** al DNS de `unmsm.edu.pe`, o los correos acaban en spam: también pasa por
él, así que no evita la gestión.

En los cuatro casos, tras cambiarlo: `php artisan config:clear` y
`php artisan correo:probar`.

### Mientras tanto

Lo único que queda inutilizable hasta que haya transporte es **recuperar la
contraseña** del panel. No hay comando para eso; se hace con tinker, en el
servidor:

```bash
php artisan tinker
>>> $u = App\Models\User::where('email', 'correo@unmsm.edu.pe')->first();
>>> $u->password = Hash::make('la-nueva'); $u->save();
```

El enlace de recuperación que envía la web sigue existiendo y funcionando: lo
único que falta es el transporte que lo entregue.

**El remitente ya está puesto** en `.env` y `.env.example`:

```
MAIL_FROM_ADDRESS="examensuficiencia.flch@unmsm.edu.pe"
MAIL_FROM_NAME="OESI · Letras UNMSM"
```

Solo faltan las cuatro líneas de conexión de arriba, que son credenciales.

El remitente es un buzón real del dominio `unmsm.edu.pe`, que es lo que exigen
SPF y DKIM: antes estaba en `hello@example.com` —el valor de fábrica de
Laravel— y con eso los correos habrían acabado rechazados o en spam.

Tras rellenarlo:

```bash
php artisan config:clear
php artisan correo:probar                    # al correo de la oficina
php artisan correo:probar tu@correo.pe       # o a donde prefieras
```

El comando envía un mensaje de prueba, avisa si sigue en modo `log` y, si el
envío falla, lista las causas habituales.

## Aviso sobre la caché de configuración

Si en producción se ejecuta `php artisan config:cache`, **hay que volver a
ejecutarlo cada vez que cambie el `.env`** o los valores viejos seguirán
activos. Esta caché ya causó un problema en este proyecto: dejó `APP_ENV`
fijado y una tanda de tests corrió contra la base de datos de desarrollo.

## Pendiente de decidir

- **Verificación de correo al crear usuarios.** Las rutas de verificación no
  están publicadas (los tests de Breeze que fallan son justo por eso). No hace
  falta mientras las cuentas las cree un administrador a mano.

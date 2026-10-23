<?php

namespace App\Http\Controllers;

use App\Filament\Pages\OficinaDeSuficiencia;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

/**
 * Vista previa de borradores.
 *
 * Con Blade, guardar y ver eran el mismo paso. Al separar el sitio dejó de
 * serlo: el HTML se genera en un build y la API solo sirve lo publicado, así
 * que un borrador no existía en ninguna parte hasta publicarlo — y publicarlo
 * es justo lo que se quiere evitar mientras se revisa.
 *
 * Aquí no se dibuja nada: lo que se enseña **es la página real**, generada por
 * el mismo `astro build` que publica, con dos diferencias —lleva el token que
 * hace que la API sirva borradores, y escribe en `dist-vista-previa/` en vez de
 * en lo que publica Nginx—. De ahí que no haya un segundo diseño que mantener
 * ni que se pueda desincronizar: si la ficha cambia, la vista previa cambia con
 * ella porque son el mismo fichero.
 *
 * El precio es el tiempo: un build completo son unos 17 segundos medidos. Se eligió a
 * conciencia frente a la alternativa —renderizar en servidor, que sería
 * instantáneo—, porque esa exige añadir un proceso Node permanente a producción
 * y cambiar la forma del despliegue.
 *
 * El directorio de vista previa vive en el contenedor de build, no aquí, así
 * que los ficheros se piden por HTTP con el mismo token. Es también lo que hace
 * que esto siga funcionando el día que el build se mueva a CI.
 */
class VistaPreviaController extends Controller
{
    /**
     * Encarga el render del sitio y devuelve la pantalla de espera.
     *
     * Abre el inicio, con el contenido tal como está guardado aunque siga en
     * borrador; desde ahí se navega al resto de páginas de la vista previa.
     */
    public function sitio()
    {
        abort_unless($this->configurada(), 503, 'La vista previa no está configurada.');

        $this->encargar();

        return view('vista-previa', [
            'titulo' => 'el sitio de la OESI',
            'ruta' => '/',
            'estadoUrl' => route('gestion.vista-previa.estado'),
            // Se pregunta a la página en vez de escribir la ruta: Filament la
            // compone a partir del id del panel, que puede cambiar.
            'volver' => OficinaDeSuficiencia::getUrl(),
        ]);
    }

    /** Estado del render, que consulta la pantalla de espera. */
    public function estado()
    {
        abort_unless($this->configurada(), 503);

        $respuesta = Http::withToken($this->token())
            ->timeout(10)
            ->get($this->base() . '/vista-previa/estado');

        return response()->json($respuesta->json() ?? ['construyendo' => false]);
    }

    /**
     * Sirve un fichero del render.
     *
     * Pasa por aquí y no por Nginx a propósito: es contenido sin publicar, y
     * este es el único punto del sistema donde ya se sabe quién pregunta.
     */
    public function archivo(Request $peticion, string $ruta = '/')
    {
        abort_unless($this->configurada(), 503);

        $respuesta = Http::withToken($this->token())
            ->timeout(30)
            ->get($this->base() . '/vista-previa/archivo', ['ruta' => '/' . ltrim($ruta, '/')]);

        $tipo = $respuesta->header('Content-Type') ?: 'application/octet-stream';
        $cuerpo = $respuesta->body();

        // El HTML sale del build con las rutas del sitio publicado
        // (`/_astro/...`). Servido desde aquí, esas rutas irían a buscar los
        // ficheros del sitio real, que no los tiene: el build de vista previa
        // genera su propio CSS —incluye el aviso amarillo, que el sitio
        // publicado no tiene— y por tanto con otro hash. Sin esta sustitución
        // la vista previa se vería sin estilos.
        if (str_contains($tipo, 'text/html')) {
            $cuerpo = str_replace(
                ['"/_astro/', "'/_astro/"],
                [
                    '"' . route('gestion.vista-previa.archivo', ['ruta' => '_astro']) . '/',
                    "'" . route('gestion.vista-previa.archivo', ['ruta' => '_astro']) . '/',
                ],
                $cuerpo
            );

            // Y los enlaces entre páginas (`href="/cronograma"`), por lo mismo:
            // sin esto, pasar del inicio a otra página de la vista previa
            // llevaba al sitio publicado, que no tiene el borrador.
            $base = rtrim(route('gestion.vista-previa.archivo'), '/');
            $cuerpo = preg_replace('#href="/(?!/)#', 'href="' . $base . '/', $cuerpo);
        }

        return new Response($cuerpo, $respuesta->status(), [
            'Content-Type' => $tipo,
            // Sin publicar: que no se quede en ninguna caché.
            'Cache-Control' => 'no-store, private',
            // Sin esto, un `<iframe>` de otro sitio podría enmarcar borradores.
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    private function encargar(): void
    {
        Http::withToken($this->token())->timeout(15)->post($this->base() . '/vista-previa');
    }

    private function configurada(): bool
    {
        return filled($this->token()) && filled(config('sitio.vista_previa.token'));
    }

    private function token(): string
    {
        return (string) config('sitio.reconstruccion.token');
    }

    private function base(): string
    {
        return rtrim((string) config('sitio.vista_previa.url'), '/');
    }
}

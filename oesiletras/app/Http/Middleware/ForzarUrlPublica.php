<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fija la raíz de las URLs generadas al valor de `app.url`.
 *
 * `asset()` y `url()` construyen las URLs absolutas a partir del host de la
 * petición. Eso funciona mientras quien pide es el navegador, pero la API la
 * consume el sitio en Astro desde dentro de la red de Docker, donde el host es
 * `web` —el nombre del servicio de Nginx—. Sin esto, las imágenes salían como
 * `http://web/images/programa-curso.webp`: una URL válida para el contenedor y
 * muerta para cualquier visitante.
 *
 * Es el fallo caracteristico de separar el frontend: el backend deja de hablar
 * solo con navegadores, y las URLs derivadas del host dejan de servir.
 */
class ForzarUrlPublica
{
    public function handle(Request $request, Closure $next): Response
    {
        $publica = config('app.url');

        if ($publica) {
            URL::forceRootUrl($publica);
        }

        return $next($request);
    }
}

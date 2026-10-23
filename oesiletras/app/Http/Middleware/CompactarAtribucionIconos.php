<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja una sola nota de licencia de Font Awesome por página.
 *
 * Cada SVG del paquete lleva incrustado su propio comentario de licencia. Como
 * los iconos se insertan en línea, una página con ~115 iconos repite el mismo
 * párrafo 115 veces: unos 23 KB, casi el 9 % del HTML de la portada.
 *
 * Los iconos de Font Awesome Free son CC BY 4.0 y **exigen atribución**, así
 * que no se pueden borrar sin más: aquí se sustituyen las repeticiones por un
 * único aviso al principio del documento, que cumple lo mismo y además es más
 * fácil de encontrar que 115 copias enterradas entre el marcado.
 */
class CompactarAtribucionIconos
{
    /** Comentario que Font Awesome incrusta en cada SVG. */
    private const PATRON = '~<!--!\s*Font Awesome[^>]*?-->~s';

    private const AVISO = '<!-- Iconos: Font Awesome Free (fontawesome.com) — Iconos CC BY 4.0, '
        . 'Fuentes SIL OFL 1.1, Código MIT. https://fontawesome.com/license/free -->';

    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        if (! $this->esHtml($respuesta)) {
            return $respuesta;
        }

        $html = $respuesta->getContent();

        if (! is_string($html) || ! str_contains($html, '<!--! Font Awesome')) {
            return $respuesta;
        }

        $limpio = preg_replace(self::PATRON, '', $html);

        // Si la expresión falla (p. ej. por límite de retroceso), se devuelve el
        // HTML original: mejor pagar los KB que servir una página rota.
        if (! is_string($limpio)) {
            return $respuesta;
        }

        // `setContent()` reemplaza también `original`, que es la vista que
        // devolvió el controlador. Perderla deja inservibles `assertViewHas` y
        // `viewData()` en los tests, así que se restaura después.
        $original = $respuesta->original;
        $respuesta->setContent($this->insertarAviso($limpio));
        $respuesta->original = $original;

        return $respuesta;
    }

    private function esHtml(Response $respuesta): bool
    {
        return $respuesta instanceof \Illuminate\Http\Response
            && str_contains((string) $respuesta->headers->get('Content-Type'), 'text/html');
    }

    /** El aviso va tras <head> y, si no lo hubiera, al principio del documento. */
    private function insertarAviso(string $html): string
    {
        $posicion = stripos($html, '<head>');

        if ($posicion === false) {
            return self::AVISO . $html;
        }

        return substr_replace($html, "\n" . self::AVISO, $posicion + 6, 0);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Visita;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Recibe el aviso de visita que manda cada página del sitio público.
 *
 * El sitio es HTML estático servido por Nginx: Laravel no ve esas peticiones,
 * así que la página avisa ella misma con `navigator.sendBeacon`. Responde
 * siempre 204 —también a lo que descarta—: quien mira la consola de su
 * navegador no gana nada sabiendo si su visita contó.
 *
 * Lo que llega se trata como dato hostil. La ruta debe tener forma de ruta, el
 * dispositivo se deduce del ancho en vez de aceptarse tal cual, y de la
 * procedencia solo se guarda el dominio: así nadie puede llenar la tabla de
 * valores inventados más allá de lo que deja el límite de peticiones.
 */
class VisitaController extends Controller
{
    /** Agentes que se anuncian como robots. Los que no se anuncian, cuentan. */
    private const BOTS = '/bot|crawl|spider|slurp|preview|fetch|monitor|headless|lighthouse|curl|wget|python|httpclient/i';

    public function registrar(Request $request): Response
    {
        $datos = $request->validate([
            'ruta' => ['required', 'string', 'max:100', 'regex:#^/[a-z0-9/_-]*$#'],
            'ancho' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'referente' => ['nullable', 'string', 'max:500'],
            'nueva' => ['nullable', 'boolean'],
        ]);

        if (! preg_match(self::BOTS, (string) $request->userAgent())) {
            Visita::registrar(
                rtrim($datos['ruta'], '/') ?: '/',
                $this->dispositivo($datos['ancho'] ?? null),
                $this->dominio($datos['referente'] ?? null, $request->getHost()),
                (bool) ($datos['nueva'] ?? false),
            );
        }

        return response()->noContent();
    }

    private function dispositivo(?int $ancho): string
    {
        return match (true) {
            $ancho === null || $ancho === 0 => 'desconocido',
            $ancho < 768 => 'movil',
            $ancho < 1024 => 'tablet',
            default => 'escritorio',
        };
    }

    /** El dominio de procedencia, sin `www.`; null si es el propio sitio o no hay. */
    private function dominio(?string $referente, string $propio): ?string
    {
        $host = $referente ? parse_url($referente, PHP_URL_HOST) : null;

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = preg_replace('/^www\./', '', strtolower($host));

        return $host === preg_replace('/^www\./', '', strtolower($propio)) ? null : substr($host, 0, 150);
    }
}

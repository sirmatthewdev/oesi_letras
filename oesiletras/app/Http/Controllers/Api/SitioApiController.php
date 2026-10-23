<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\OesiSetting;
use Illuminate\Http\JsonResponse;

/**
 * Identidad y navegación del sitio: lo que pintan la cabecera y el pie.
 *
 * Sale de la misma fila que la página (`oesi_settings`), y se sirve aunque la
 * página esté en borrador: la cabecera y el pie de la portada de «próximamente»
 * también necesitan el nombre y el correo de la oficina.
 */
class SitioApiController extends Controller
{
    public function configuracion(): JsonResponse
    {
        $o = OesiSetting::actual();
        $whatsapp = OesiSetting::whatsapp($o?->whatsapp_numero, $o?->whatsapp_mensaje);

        return response()->json([
            'data' => [
                'nombre' => $o?->nombre ?: 'Oficina de Examen de Suficiencia en Idiomas',
                'descripcion' => $o?->descripcion ?: null,
                // Sin logotipo propio, el de la Facultad, a la que pertenece la
                // oficina: mejor una marca institucional cierta que un hueco.
                'logo' => $this->imagen($o?->logo_path) ?? asset('images/logo-letras.webp'),
                'favicon' => $this->imagen($o?->favicon_path),
                'contacto' => [
                    'email' => $o?->email ?: null,
                    'telefono' => $whatsapp['visible'] ?? null,
                    'whatsapp' => $whatsapp['enlace'] ?? null,
                    'direccion' => $o?->direccion ?: null,
                    'horario' => $o?->horario ? trim($o->horario) : null,
                ],
                'facultad' => [
                    'web' => $o?->web_facultad ?: null,
                ],
                'redes' => collect(is_array($o?->redes) ? $o->redes : [])
                    ->filter(fn ($r) => is_array($r) && filled($r['red'] ?? null) && filled($r['url'] ?? null))
                    ->mapWithKeys(fn ($r) => [$r['red'] => $r['url']])
                    ->all() ?: new \stdClass(),
            ],
        ]);
    }

    /**
     * El menú, ya resuelto y anidado.
     *
     * Las URLs salen del accesor `enlace`, que prefiere `route_name` sobre una
     * URL escrita a mano —sobrevive a un cambio de ruta— y descarta las rutas
     * que ya no existen.
     */
    public function menu(): JsonResponse
    {
        $raiz = MenuItem::query()
            ->visibles()
            ->whereNull('parent_id')
            ->with(['hijos' => fn ($q) => $q->visibles()])
            ->orderBy('orden')
            ->get();

        return response()->json([
            'data' => $raiz
                ->map(fn (MenuItem $item) => $this->comoArray($item))
                // Una entrada sin destino y sin hijos apuntaría a un enlace
                // vacío en la barra: mejor que falte.
                ->reject(fn (array $item) => $item['enlace'] === null && $item['hijos'] === [])
                ->values(),
        ]);
    }

    private function comoArray(MenuItem $item): array
    {
        $hijos = $item->hijos
            ->map(fn (MenuItem $hijo) => $this->comoArray($hijo))
            ->reject(fn (array $hijo) => $hijo['enlace'] === null && $hijo['hijos'] === [])
            ->values();

        return [
            'etiqueta' => $item->etiqueta,
            'enlace' => $item->enlace,
            'nueva_pestana' => (bool) $item->nueva_pestana,
            'hijos' => $hijos->all(),
        ];
    }

    /**
     * URL de una imagen subida desde el panel.
     *
     * `logo_path` es una ruta DENTRO del disco público —«sitio/xxx.webp»—, no
     * una dirección: entregada tal cual, Astro recibiría una cadena que no
     * apunta a ninguna parte.
     */
    private function imagen(?string $ruta): ?string
    {
        return filled($ruta) ? asset('storage/' . $ruta) : null;
    }
}

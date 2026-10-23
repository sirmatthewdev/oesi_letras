<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OesiSetting;
use App\Support\VistaPrevia;
use Illuminate\Http\JsonResponse;

/**
 * Contenido de la Oficina de Examen de Suficiencia en Idiomas, para /oesi.
 *
 * Responde `data: null` mientras la página no esté publicada, y el sitio no
 * genera la ruta: /oesi da 404 en vez de enseñar una página a medio revisar. La
 * vista previa sí la ve, igual que ve los cursos en borrador.
 *
 * Las cifras de la cabecera tienen su propia compuerta. Publicar la página no
 * las publica: hace falta marcarlas como verificadas, porque vienen de la
 * landing anterior sin documento que las respalde.
 *
 * Las listas se devuelven siempre como listas, aunque estén vacías, y los
 * importes como números: el formato monetario lo pone el sitio, en un solo
 * sitio, y no cada fila escrita a mano.
 */
class OesiApiController extends Controller
{
    public function mostrar(): JsonResponse
    {
        $previa = VistaPrevia::activa();
        $oesi = OesiSetting::actual();

        $visible = $oesi && ($oesi->estaPublicada() || $previa);

        $respuesta = response()->json(['data' => $visible ? $this->contenido($oesi, $previa) : null]);

        if ($previa) {
            $respuesta->header('Cache-Control', 'no-store, private');
        }

        return $respuesta;
    }

    /** @return array<string, mixed> */
    private function contenido(OesiSetting $o, bool $previa): array
    {
        return [
            'estado' => $o->estado,
            'nombre' => $o->nombre,
            'institucion' => $o->institucion,
            'bajada' => $o->bajada ?: null,

            'contacto' => [
                'email' => $o->email ?: null,
                'horario' => $o->horario ? trim($o->horario) : null,
                'whatsapp' => OesiSetting::whatsapp($o->whatsapp_numero, $o->whatsapp_mensaje),
            ],

            // Clave → URL, la forma que ya entiende el componente de redes.
            'redes' => collect($this->lista($o->redes))
                ->filter(fn ($r) => filled($r['red'] ?? null) && filled($r['url'] ?? null))
                ->mapWithKeys(fn ($r) => [$r['red'] => $r['url']])
                ->all() ?: new \stdClass(),

            'estadisticas' => [
                'verificadas' => (bool) $o->estadisticas_verificadas,
                // Sin verificar solo las ve la vista previa, que es donde se
                // revisan; el sitio publicado no las recibe siquiera.
                'cifras' => ($o->estadisticas_verificadas || $previa)
                    ? array_map(fn ($c) => [
                        'valor' => (int) ($c['valor'] ?? 0),
                        'prefijo' => (string) ($c['prefijo'] ?? ''),
                        'sufijo' => (string) ($c['sufijo'] ?? ''),
                        'etiqueta' => (string) ($c['etiqueta'] ?? ''),
                    ], $this->lista($o->estadisticas))
                    : [],
            ],

            'curso' => [
                'titulo' => $o->curso_titulo,
                'bajada' => $o->curso_bajada ?: null,
                'imagen' => $this->imagen($o->curso_imagen, $o->curso_imagen_alt),
                'url' => $o->curso_url ?: null,
                'whatsapp' => OesiSetting::whatsapp($o->curso_whatsapp_numero, $o->curso_whatsapp_mensaje),
            ],

            'examen' => [
                'objetivo' => $o->examen_objetivo,
                'destinatarios' => $this->textos($o->examen_destinatarios),
                'jurado' => $o->examen_jurado ?: null,
                'escala_intro' => $o->escala_intro ?: null,
                'escala' => array_map(fn ($n) => [
                    'letra' => (string) ($n['letra'] ?? ''),
                    'nivel' => (string) ($n['nivel'] ?? ''),
                    'rango' => (string) ($n['rango'] ?? ''),
                    'subniveles' => array_map(fn ($s) => [
                        'codigo' => (string) ($s['codigo'] ?? ''),
                        'nota' => (string) ($s['nota'] ?? ''),
                        'descripcion' => ($s['descripcion'] ?? null) ?: null,
                    ], $this->lista($n['subniveles'] ?? [])),
                ], $this->lista($o->escala)),
                'vigencia_intro' => $o->vigencia_intro ?: null,
                'vigencia' => array_map(fn ($v) => [
                    'nivel' => (string) ($v['nivel'] ?? ''),
                    'vigencia' => (string) ($v['vigencia'] ?? ''),
                ], $this->lista($o->vigencia)),
                'vigencia_nota' => $o->vigencia_nota ?: null,
                'resultados' => [
                    'aprobados' => $o->resultados_aprobados ?: null,
                    'desaprobados' => $o->resultados_desaprobados ?: null,
                    'virtual' => $o->resultados_virtual ?: null,
                ],
                'requisitos' => $o->requisitos ?: null,
            ],

            'habilidades' => [
                'intro' => $o->habilidades_intro ?: null,
                'items' => array_map(fn ($h) => [
                    'titulo' => (string) ($h['titulo'] ?? ''),
                    'descripcion' => (string) ($h['descripcion'] ?? ''),
                    'icono' => ($h['icono'] ?? null) ?: null,
                ], $this->lista($o->habilidades)),
            ],

            'modalidades' => [
                'intro' => $o->modalidades_intro ?: null,
                'items' => array_map(fn ($m) => [
                    'modalidad' => ($m['modalidad'] ?? null) ?: null,
                    'titulo' => (string) ($m['titulo'] ?? ''),
                    'icono' => ($m['icono'] ?? null) ?: null,
                    'subtitulo' => ($m['subtitulo'] ?? null) ?: null,
                    'introduccion' => ($m['introduccion'] ?? null) ?: null,
                    'reglas' => $this->textos($m['reglas'] ?? []),
                    'nota' => ($m['nota'] ?? null) ?: null,
                    'nota_tipo' => ($m['nota_tipo'] ?? null) === 'aviso' ? 'aviso' : 'informacion',
                ], $this->lista($o->modalidades)),
            ],

            'cronograma' => [
                'intro' => $o->cronograma_intro ?: null,
                'imagen' => $this->imagen($o->cronograma_imagen, $o->cronograma_imagen_alt),
                'aviso' => $o->cronograma_aviso ?: null,
                'formularios' => $this->enlaces($o->cronograma_formularios),
            ],

            'inscripcion' => [
                'intro' => $o->inscripcion_intro ?: null,
                'imagenes' => [
                    'proceso_1' => $this->imagen($o->proceso_imagen_1, $o->proceso_imagen_1_alt),
                    'proceso_2' => $this->imagen($o->proceso_imagen_2, $o->proceso_imagen_2_alt),
                    'medio_pago' => $this->imagen($o->medio_pago_imagen, $o->medio_pago_imagen_alt),
                ],
                'costos_intro' => $o->costos_intro ?: null,
                'costos' => array_map(fn ($c) => [
                    'concepto' => (string) ($c['concepto'] ?? ''),
                    'monto' => $this->monto($c['monto'] ?? null),
                ], $this->lista($o->costos)),
                'pago' => [
                    'banco' => $o->pago_banco ?: null,
                    'codigo' => $o->pago_codigo ?: null,
                    'advertencias' => $this->textos($o->pago_advertencias),
                ],
                'tarifario' => array_map(fn ($t) => [
                    'concepto' => (string) ($t['concepto'] ?? ''),
                    'monto' => $this->monto($t['monto'] ?? null),
                    'url_pago' => ($t['url_pago'] ?? null) ?: null,
                    'distintivo' => ($t['distintivo'] ?? null) ?: null,
                ], $this->lista($o->tarifario)),
                'tarifario_nota' => $o->tarifario_nota ?: null,
                'documentos' => $this->textos($o->documentos),
                'formularios' => $this->enlaces($o->formularios),
                'cierre' => $o->inscripcion_cierre ?: null,
            ],

            'faqs' => [
                'intro' => $o->faqs_intro ?: null,
                // Los temas, en su orden, para que la página los pinte sin
                // tener que conocerlos.
                'temas' => array_map(
                    fn (string $clave, string $etiqueta) => ['clave' => $clave, 'etiqueta' => $etiqueta],
                    array_keys(OesiSetting::TEMAS_FAQ),
                    OesiSetting::TEMAS_FAQ
                ),
                'items' => array_map(fn ($f) => [
                    'pregunta' => (string) ($f['pregunta'] ?? ''),
                    'respuesta' => (string) ($f['respuesta'] ?? ''),
                    'lista' => $this->textos($f['lista'] ?? []),
                    // Un tema que ya no existe cuenta como ninguno: la pregunta
                    // va a «Otras» en vez de desaparecer.
                    'categoria' => array_key_exists($f['categoria'] ?? '', OesiSetting::TEMAS_FAQ) ? $f['categoria'] : null,
                ], $this->lista($o->faqs)),
            ],
        ];
    }

    /**
     * Una lista de verdad, con índices 0..n.
     *
     * El repetidor de Filament guarda sus filas con claves UUID; servidas tal
     * cual, el JSON sería un objeto y no un array, y el sitio no podría
     * recorrerlo.
     *
     * @return list<array<string, mixed>>
     */
    private function lista(mixed $valor): array
    {
        return is_array($valor) ? array_values(array_filter($valor, 'is_array')) : [];
    }

    /**
     * Listas de texto: las escribe un repetidor de una sola casilla, que guarda
     * `[{texto: …}]`, o un JSON plano de cadenas como el de la migración. Se
     * admiten las dos formas.
     *
     * @return list<string>
     */
    private function textos(mixed $valor): array
    {
        if (! is_array($valor)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn ($t) => trim((string) (is_array($t) ? ($t['texto'] ?? '') : $t)), $valor),
            fn (string $t) => $t !== ''
        ));
    }

    /** @return list<array{nombre: string, url: string}> */
    private function enlaces(mixed $valor): array
    {
        return array_values(array_map(
            fn ($e) => [
                'nombre' => (string) $e['nombre'],
                'url' => (string) $e['url'],
                'descripcion' => filled($e['descripcion'] ?? null) ? (string) $e['descripcion'] : null,
            ],
            array_filter($this->lista($valor), fn ($e) => filled($e['nombre'] ?? null) && filled($e['url'] ?? null))
        ));
    }

    /** @return array{url: string, alt: string}|null */
    private function imagen(?string $url, ?string $alt): ?array
    {
        return filled($url) ? ['url' => $url, 'alt' => (string) $alt] : null;
    }

    private function monto(mixed $valor): ?float
    {
        return is_numeric($valor) ? round((float) $valor, 2) : null;
    }
}

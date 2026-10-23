<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * La Oficina de Examen de Suficiencia en Idiomas: el contenido entero del
 * sitio, en una fila.
 *
 * Identidad (nombre, logo, contacto, redes) y página van juntos a propósito:
 * el correo o el WhatsApp de la cabecera y los de la página son el mismo dato,
 * y en dos tablas acabarían diciendo cosas distintas. Se edita en el panel, en
 * «Contenido del sitio».
 *
 * La forma de cada JSON la fija el formulario del panel
 * (`App\Filament\Pages\OficinaDeSuficiencia`), que es quien la escribe, y la
 * comprueba `OesiTest` contra `data/oesi.json`. Escribir otra cosa a mano no
 * falla al guardar: falla al pintar la página.
 */
class OesiSetting extends Model
{
    public const ESTADO_BORRADOR = 'borrador';

    public const ESTADO_PUBLICADO = 'publicado';

    /**
     * Temas de las preguntas frecuentes, en el orden en que salen en la página.
     *
     * Aquí y en ningún otro sitio: el panel los ofrece de esta lista y la API la
     * sirve con sus rótulos, así que añadir un tema no exige tocar la plantilla.
     * Una pregunta sin tema, o con uno que ya no existe, va a «Otras preguntas».
     */
    public const TEMAS_FAQ = [
        'fechas' => 'Fechas y modalidades',
        'requisitos' => 'Requisitos',
        'inscripcion' => 'Inscripción y pagos',
        'resultados' => 'Resultados y certificado',
    ];

    protected $table = 'oesi_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'redes' => 'array',
        'estadisticas' => 'array',
        'estadisticas_verificadas' => 'boolean',
        'examen_destinatarios' => 'array',
        'escala' => 'array',
        'vigencia' => 'array',
        'habilidades' => 'array',
        'modalidades' => 'array',
        'cronograma_formularios' => 'array',
        'costos' => 'array',
        'pago_advertencias' => 'array',
        'tarifario' => 'array',
        'documentos' => 'array',
        'formularios' => 'array',
        'faqs' => 'array',
    ];

    protected static function booted(): void
    {
        // Una sola fila, como `site_settings`: una segunda sería una OESI que
        // ninguna página consulta y que confundiría a quien edita.
        static::creating(function () {
            if (static::query()->exists()) {
                throw new \LogicException('La OESI ya tiene su fila; se edita, no se crea otra.');
            }
        });
    }

    /** La fila de la OESI, o null si todavía no se ha sembrado. */
    public static function actual(): ?self
    {
        return static::query()->first();
    }

    public function estaPublicada(): bool
    {
        return $this->estado === self::ESTADO_PUBLICADO;
    }

    /**
     * Enlace de WhatsApp compuesto a partir del número y el mensaje.
     *
     * El enlace no se guarda: se calcula. En la landing anterior el enlace del
     * curso estaba escrito a mano en el HTML aparte del número configurado, y
     * cambiar uno no cambiaba el otro.
     *
     * Acepta el número con espacios, guiones o «+». Con nueve dígitos se toma
     * como móvil peruano y se antepone el 51, igual que `SiteSetting::whatsappUrl`.
     *
     * @return array{numero: string, visible: string, enlace: string}|null
     */
    public static function whatsapp(?string $numero, ?string $mensaje = null): ?array
    {
        $digitos = preg_replace('/\D/', '', (string) $numero);

        if ($digitos === '') {
            return null;
        }

        if (strlen($digitos) === 9) {
            $digitos = '51' . $digitos;
        }

        $enlace = 'https://wa.me/' . $digitos;

        if (filled($mensaje)) {
            $enlace .= '?text=' . rawurlencode($mensaje);
        }

        return [
            'numero' => $digitos,
            // «+51 914 033 108». La propiedad `whatsappVisible` de la landing
            // anterior se usaba sin estar declarada, y el dato salía vacío: se
            // deriva del número para que no pueda volver a faltar.
            'visible' => self::visible($digitos),
            'enlace' => $enlace,
        ];
    }

    private static function visible(string $digitos): string
    {
        if (strlen($digitos) === 11 && str_starts_with($digitos, '51')) {
            return '+51 ' . implode(' ', str_split(substr($digitos, 2), 3));
        }

        return '+' . $digitos;
    }
}

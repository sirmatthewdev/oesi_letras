<?php

namespace Database\Seeders;

use App\Models\OesiSetting;
use Illuminate\Database\Seeder;

/**
 * Contenido de la Oficina de Examen de Suficiencia en Idiomas (OESI).
 *
 * Los textos, precios y enlaces viven en data/oesi.json, que es la migración de
 * la landing que la oficina tenía publicada en letras.unmsm.edu.pe. Este seeder
 * los carga una vez; a partir de ahí se editan en el panel, en «Oficina de
 * Suficiencia», que es donde la oficina mantiene su contenido.
 *
 * **Publicado y con las cifras verificadas**, porque así lo dice el fichero de
 * datos: la oficina validó el contenido, las cifras y los enlaces de San Market
 * el 20-10-2026. Si el fichero no dijera nada, entraría en borrador y con las
 * cifras sin verificar: publicar tiene que ser una decisión escrita, no un
 * valor por defecto.
 *
 * La guarda: si la fila ya tiene contenido, no se toca.
 * Sobrescribir lo que la oficina editó en el panel con el contenido de la
 * migración sería deshacer su trabajo sin avisar.
 */
class OesiSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * La guarda pregunta si hay CONTENIDO, no si existe la fila.
         *
         * Abrir la página del panel crea la fila vacía si no está (como hace
         * Configuración). Con un `exists()` aquí, quien entrara al panel antes de
         * sembrar dejaría una fila en blanco y el seeder se creería cargado: el
         * mismo fallo que dejó una instalación limpia sin sus 39 cursos.
         */
        $existente = OesiSetting::actual();

        if ($existente && filled($existente->nombre)) {
            $this->command?->warn('La OESI ya está cargada; no se toca.');

            return;
        }

        $datos = json_decode(
            file_get_contents(database_path('seeders/data/oesi.json')),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        // Las claves que empiezan por «_» son notas para quien lee el fichero.
        $datos = array_filter($datos, fn (string $clave) => ! str_starts_with($clave, '_'), ARRAY_FILTER_USE_KEY);

        $contenido = [
            ...$datos,
            'estado' => ($datos['estado'] ?? null) === OesiSetting::ESTADO_PUBLICADO
                ? OesiSetting::ESTADO_PUBLICADO
                : OesiSetting::ESTADO_BORRADOR,
            'estadisticas_verificadas' => ($datos['estadisticas_verificadas'] ?? false) === true,
        ];

        $existente ? $existente->update($contenido) : OesiSetting::query()->create($contenido);

        $this->command?->info(sprintf(
            'OESI: %d preguntas frecuentes, %d tarifas y %d formularios, %s.',
            count($datos['faqs']),
            count($datos['tarifario']),
            count($datos['formularios']) + count($datos['cronograma_formularios']),
            $contenido['estado'] === OesiSetting::ESTADO_PUBLICADO ? 'publicada' : 'en borrador'
        ));
    }
}

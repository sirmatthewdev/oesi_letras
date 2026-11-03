<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El cronograma deja de ser una imagen: el sitio pinta las fechas cargadas en
 * el panel. La presentación sembrada hablaba de «esta imagen», así que se
 * cambia, pero solo si sigue siendo la sembrada: si la oficina la reescribió,
 * se respeta.
 *
 * Las columnas de la imagen (`cronograma_imagen`, `cronograma_imagen_alt`) no
 * se borran: el sitio ya no las usa, pero conservan la última publicación por
 * si se quiere consultar.
 */
return new class extends Migration
{
    private const ANTES = 'Fechas programadas para los exámenes de suficiencia. Esta imagen se actualiza cada mes; si no llegara a cargar, usa el enlace de respaldo o escríbenos por WhatsApp.';

    private const AHORA = 'Fechas programadas para los exámenes de suficiencia, en hora de Lima. La inscripción cierra dos días antes de cada examen; si tienes dudas, escríbenos por WhatsApp o al correo.';

    public function up(): void
    {
        DB::table('oesi_settings')->where('cronograma_intro', self::ANTES)->update(['cronograma_intro' => self::AHORA]);
    }

    public function down(): void
    {
        DB::table('oesi_settings')->where('cronograma_intro', self::AHORA)->update(['cronograma_intro' => self::ANTES]);
    }
};

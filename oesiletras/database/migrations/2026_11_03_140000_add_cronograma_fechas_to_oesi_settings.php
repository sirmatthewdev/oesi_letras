<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Las fechas del cronograma como datos, una fila por examen.
 *
 * Hasta ahora el cronograma era solo una imagen: el sitio no podía decir cuál
 * es el próximo examen ni mostrarlo en su idioma. La imagen se queda como
 * respaldo; las fechas se cargan aquí cada mes.
 *
 * Se rellena con las de `data/oesi.json` (las de la imagen vigente) solo si la
 * columna está vacía: lo que la oficina ya haya escrito no se pisa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oesi_settings', function (Blueprint $table) {
            $table->json('cronograma_fechas')->nullable()->after('cronograma_aviso');
        });

        $datos = json_decode(
            file_get_contents(database_path('seeders/data/oesi.json')),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        DB::table('oesi_settings')
            ->whereNull('cronograma_fechas')
            ->whereNotNull('nombre')
            ->update(['cronograma_fechas' => json_encode($datos['cronograma_fechas'], JSON_UNESCAPED_UNICODE)]);
    }

    public function down(): void
    {
        Schema::table('oesi_settings', function (Blueprint $table) {
            $table->dropColumn('cronograma_fechas');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los idiomas que se evalúan, uno por pestaña del hero de la portada.
 *
 * La fila de la OESI ya está sembrada en producción y el seeder no la vuelve a
 * tocar (ver `OesiSeeder`), así que sin esto la columna nueva llegaría vacía y
 * el hero no saldría hasta que alguien la llenara a mano. Se rellena con la
 * lista de `data/oesi.json` —la misma que siembra una instalación limpia— y
 * solo si está vacía: lo que la oficina ya haya escrito no se pisa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oesi_settings', function (Blueprint $table) {
            $table->json('idiomas')->nullable()->after('requisitos');
        });

        $datos = json_decode(
            file_get_contents(database_path('seeders/data/oesi.json')),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        DB::table('oesi_settings')
            ->whereNull('idiomas')
            ->whereNotNull('nombre')
            ->update(['idiomas' => json_encode($datos['idiomas'], JSON_UNESCAPED_UNICODE)]);
    }

    public function down(): void
    {
        Schema::table('oesi_settings', function (Blueprint $table) {
            $table->dropColumn('idiomas');
        });
    }
};

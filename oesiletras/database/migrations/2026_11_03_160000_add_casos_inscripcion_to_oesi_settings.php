<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los casos del asistente de inscripción: para cada perfil, qué derecho de
 * examen paga y qué formulario envía.
 *
 * No se siembra ninguno a propósito. La relación entre perfil, tarifa y
 * formulario la decide la oficina, y equivocarla haría pagar un concepto que
 * no corresponde: mientras no los escriba, la página de inscripción muestra
 * todas las opciones, como hasta ahora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oesi_settings', function (Blueprint $table) {
            $table->json('casos_inscripcion')->nullable()->after('inscripcion_cierre');
        });
    }

    public function down(): void
    {
        Schema::table('oesi_settings', function (Blueprint $table) {
            $table->dropColumn('casos_inscripcion');
        });
    }
};

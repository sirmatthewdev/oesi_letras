<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La versión en blanco del logotipo, para el pie.
 *
 * El pie es azul oscuro y hasta ahora blanqueaba el logotipo de la cabecera
 * con un filtro: sirve para uno de un solo color, pero uno con varios colores
 * o con detalles finos queda como una silueta plana. La oficina tiene su
 * versión en blanco hecha por el diseñador; aquí se guarda. Vacía, el pie
 * sigue con el filtro de siempre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oesi_settings', function (Blueprint $table) {
            $table->string('logo_blanco_path')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('oesi_settings', function (Blueprint $table) {
            $table->dropColumn('logo_blanco_path');
        });
    }
};

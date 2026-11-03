<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El enlace al Libro de Reclamaciones, para el pie del sitio.
 *
 * Una entidad pública lo ofrece en su web; la dirección la da la universidad,
 * así que no se siembra ninguna: hasta que la oficina la escriba en el panel,
 * el pie no pinta el enlace (mejor que falte a que lleve a un sitio equivocado).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oesi_settings', function (Blueprint $table) {
            $table->string('libro_reclamaciones', 500)->nullable()->after('web_facultad');
        });
    }

    public function down(): void
    {
        Schema::table('oesi_settings', function (Blueprint $table) {
            $table->dropColumn('libro_reclamaciones');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contador de visitas del sitio público.
 *
 * Se guardan conteos, no visitas sueltas: una fila por día, dimensión y valor
 * (`2026-10-30 · pagina · /cronograma · 57`). Así no hay nada que identifique a
 * nadie —ni IP, ni navegador, ni cookie— y la tabla crece unas decenas de filas
 * al día por mucho tráfico que haya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');

            // pagina | sesion | dispositivo | referente
            $table->string('dimension', 20);
            $table->string('valor', 150);
            $table->unsignedInteger('total')->default(0);

            $table->unique(['fecha', 'dimension', 'valor']);
            $table->index(['dimension', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas');
    }
};

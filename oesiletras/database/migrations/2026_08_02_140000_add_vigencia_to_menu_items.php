<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vigencia de los enlaces del menú.
 *
 * Varias entradas cambian cada convocatoria —cuadro de vacantes, criterios de
 * evaluación— y no había nada que avisara cuando quedaban obsoletas: al hacer
 * este trabajo, «Criterios de Evaluación» seguía apuntando al documento de
 * 2025. Con una fecha de caducidad el enlace se retira solo y el panel avisa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->date('vigente_hasta')->nullable()->after('is_visible');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn('vigente_hasta');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menú de navegación editable.
 *
 * Estaba escrito a mano en `navbar.blade.php`, dos veces (escritorio y móvil).
 * Incluía enlaces con fecha de caducidad —el cuadro de vacantes de cada
 * convocatoria, los criterios de evaluación— que obligaban a tocar Blade y
 * desplegar cada vez, con el riesgo de actualizar una copia y no la otra: al
 * hacer este cambio, «Criterios de Evaluación» seguía apuntando a 2025.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()
                ->constrained('menu_items')->cascadeOnDelete();
            $table->string('etiqueta');

            // Un elemento apunta a una ruta interna (`route_name`, estable
            // frente a cambios de URL) o a una dirección externa (`url`).
            // Los que no tienen ninguna de las dos son solo cabecera de
            // desplegable.
            $table->string('route_name')->nullable();
            $table->string('url')->nullable();
            $table->string('route_params')->nullable();

            $table->string('icono')->nullable();
            $table->boolean('nueva_pestana')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};

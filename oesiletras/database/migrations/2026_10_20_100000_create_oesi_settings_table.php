<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La Oficina de Examen de Suficiencia en Idiomas (OESI), en una sola fila.
     *
     * Todo el contenido del sitio sale de aquí. Es una oficina con una página
     * informativa —requisitos, modalidades, pagos, formularios—, no un
     * catálogo: no hace falta más que una fila, editada desde una sola página
     * del panel.
     *
     * Solo estructura, sin una fila de contenido: el texto oficial lo carga
     * `OesiSeeder` desde `data/oesi.json` y después se edita en el panel (ver
     * «Dónde no debe vivir el contenido» en el README).
     *
     * Las listas van en JSON porque su número cambia —hoy son doce preguntas y
     * cinco tarifas, mañana pueden ser otras— y la forma de cada elemento la
     * fija el formulario del panel, que es quien las escribe.
     */
    public function up(): void
    {
        Schema::create('oesi_settings', function (Blueprint $table) {
            $table->id();

            // Publicación. Nace en borrador: varias condiciones de la fuente
            // (sanciones de la modalidad virtual, cifras de la cabecera) deben
            // revisarse antes de salir al público. Mientras tanto se ven en la
            // vista previa y en ningún otro sitio.
            $table->string('estado', 20)->default('borrador');

            // Identidad y contacto.
            $table->string('nombre')->nullable();
            $table->string('institucion')->nullable();
            $table->text('bajada')->nullable();
            $table->string('whatsapp_numero', 20)->nullable();
            $table->string('whatsapp_mensaje')->nullable();
            $table->string('email')->nullable();
            $table->string('horario')->nullable();
            $table->json('redes')->nullable();

            // Identidad del sitio: cabecera, pie, pestaña del navegador y lo
            // que se ve al compartir un enlace. Vive en esta misma fila y no en
            // una tabla de ajustes aparte para que el correo, el WhatsApp y las
            // redes se escriban una sola vez y no puedan contradecirse entre la
            // cabecera y la página.
            $table->text('descripcion')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->text('direccion')->nullable();
            $table->string('web_facultad', 500)->nullable();

            // Cifras de la cabecera. Separadas de la publicación de la página:
            // vienen de la landing anterior sin documento que las respalde, y
            // no salen hasta que alguien las dé por verificadas.
            $table->json('estadisticas')->nullable();
            $table->boolean('estadisticas_verificadas')->default(false);

            // Curso de preparación: un recurso relacionado, no el objeto de la
            // oficina. Lo dicta el CEID y tiene su propio contacto.
            $table->string('curso_titulo')->nullable();
            $table->text('curso_bajada')->nullable();
            $table->string('curso_imagen', 500)->nullable();
            $table->string('curso_imagen_alt')->nullable();
            $table->string('curso_url', 500)->nullable();
            $table->string('curso_whatsapp_numero', 20)->nullable();
            $table->string('curso_whatsapp_mensaje')->nullable();

            // El examen.
            $table->text('examen_objetivo')->nullable();
            $table->json('examen_destinatarios')->nullable();
            $table->text('examen_jurado')->nullable();
            $table->string('escala_intro')->nullable();
            $table->json('escala')->nullable();
            $table->string('vigencia_intro')->nullable();
            $table->json('vigencia')->nullable();
            $table->text('vigencia_nota')->nullable();
            $table->text('resultados_aprobados')->nullable();
            $table->text('resultados_desaprobados')->nullable();
            $table->text('resultados_virtual')->nullable();
            $table->text('requisitos')->nullable();

            // Habilidades y modalidades.
            $table->text('habilidades_intro')->nullable();
            $table->json('habilidades')->nullable();
            $table->text('modalidades_intro')->nullable();
            $table->json('modalidades')->nullable();

            // Cronograma: una imagen que cambia cada mes. No se guardan fechas:
            // la fuente de verdad es la publicación del mes.
            $table->text('cronograma_intro')->nullable();
            $table->string('cronograma_imagen', 500)->nullable();
            $table->string('cronograma_imagen_alt')->nullable();
            $table->text('cronograma_aviso')->nullable();
            $table->json('cronograma_formularios')->nullable();

            // Inscripción y pagos.
            $table->text('inscripcion_intro')->nullable();
            $table->string('proceso_imagen_1', 500)->nullable();
            $table->string('proceso_imagen_1_alt')->nullable();
            $table->string('proceso_imagen_2', 500)->nullable();
            $table->string('proceso_imagen_2_alt')->nullable();
            $table->string('medio_pago_imagen', 500)->nullable();
            $table->string('medio_pago_imagen_alt')->nullable();
            $table->text('costos_intro')->nullable();
            $table->json('costos')->nullable();
            $table->string('pago_banco')->nullable();
            $table->string('pago_codigo')->nullable();
            $table->json('pago_advertencias')->nullable();
            $table->json('tarifario')->nullable();
            $table->text('tarifario_nota')->nullable();
            $table->json('documentos')->nullable();
            $table->json('formularios')->nullable();
            $table->text('inscripcion_cierre')->nullable();

            // Preguntas frecuentes.
            $table->text('faqs_intro')->nullable();
            $table->json('faqs')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oesi_settings');
    }
};

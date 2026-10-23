<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\OesiSetting;
use Database\Seeders\OesiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La identidad y el menú, que pintan la cabecera y el pie.
 *
 * La identidad sale de la misma fila que la página: si el correo o el WhatsApp
 * de la cabecera vinieran de otra tabla, podrían contradecir a los de la página.
 */
class SitioApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_identidad_sale_de_la_fila_de_la_oesi(): void
    {
        $this->seed(OesiSeeder::class);

        $this->getJson('/api/v1/sitio')
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Oficina de Examen de Suficiencia en Idiomas')
            ->assertJsonPath('data.contacto.email', 'examensuficiencia.flch@unmsm.edu.pe')
            ->assertJsonPath('data.contacto.telefono', '+51 914 033 108')
            ->assertJsonPath('data.contacto.whatsapp', 'https://wa.me/51914033108?text=Hola%2C%20quiero%20consultar%20sobre%20el%20examen%20de%20suficiencia')
            ->assertJsonPath('data.facultad.web', 'https://letras.unmsm.edu.pe')
            ->assertJsonPath('data.redes.instagram', 'https://www.instagram.com/oesidiomas.unmsm/');
    }

    public function test_la_identidad_se_sirve_aunque_la_pagina_este_en_borrador(): void
    {
        $this->seed(OesiSeeder::class);
        OesiSetting::actual()->update(['estado' => OesiSetting::ESTADO_BORRADOR]);

        // La portada de «próximamente» también lleva cabecera y pie.
        $this->getJson('/api/v1/sitio')->assertOk()->assertJsonPath('data.contacto.email', 'examensuficiencia.flch@unmsm.edu.pe');
        $this->getJson('/api/v1/oesi')->assertOk()->assertJsonPath('data', null);
    }

    public function test_sin_fila_la_identidad_no_revienta(): void
    {
        // Una instalación sin sembrar no debe tumbar el build del sitio.
        $this->getJson('/api/v1/sitio')
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Oficina de Examen de Suficiencia en Idiomas')
            ->assertJsonPath('data.contacto.email', null)
            ->assertJsonPath('data.logo', fn ($logo) => str_ends_with($logo, 'images/logo-letras.webp'));
    }

    public function test_el_logo_subido_sale_como_url(): void
    {
        $this->seed(OesiSeeder::class);
        OesiSetting::actual()->update(['logo_path' => 'sitio/logo.webp']);

        $this->assertStringEndsWith('/storage/sitio/logo.webp', $this->getJson('/api/v1/sitio')->json('data.logo'));
    }

    public function test_el_menu_lleva_a_las_secciones_con_su_ancla(): void
    {
        $padre = MenuItem::create(['etiqueta' => 'Examen', 'orden' => 0, 'is_visible' => true]);
        MenuItem::create([
            'etiqueta' => 'Inscripción', 'route_name' => 'inscripcion',
            'parent_id' => $padre->id, 'orden' => 0, 'is_visible' => true,
        ]);

        $examen = collect($this->getJson('/api/v1/menu')->assertOk()->json('data'))->firstWhere('etiqueta', 'Examen');

        $this->assertCount(1, $examen['hijos']);
        // Ruta relativa y con su ancla: la pestaña se abre al cambiar el ancla.
        $this->assertSame('/inscripcion', $examen['hijos'][0]['enlace']);
    }

    public function test_un_destino_que_ya_no_existe_se_omite(): void
    {
        MenuItem::create(['etiqueta' => 'Cursos', 'route_name' => 'cursos.index', 'orden' => 0, 'is_visible' => true]);

        $this->assertNotContains('Cursos', collect($this->getJson('/api/v1/menu')->json('data'))->pluck('etiqueta'));
    }

    public function test_los_enlaces_externos_del_menu_se_respetan_tal_cual(): void
    {
        MenuItem::create([
            'etiqueta' => 'Web Letras',
            'url' => 'https://letras.unmsm.edu.pe/',
            'nueva_pestana' => true,
            'orden' => 0,
            'is_visible' => true,
        ]);

        $item = collect($this->getJson('/api/v1/menu')->json('data'))->firstWhere('etiqueta', 'Web Letras');

        $this->assertSame('https://letras.unmsm.edu.pe/', $item['enlace']);
        $this->assertTrue($item['nueva_pestana']);
    }

    public function test_el_menu_omite_lo_oculto(): void
    {
        MenuItem::create(['etiqueta' => 'Visible', 'url' => '/a', 'orden' => 0, 'is_visible' => true]);
        MenuItem::create(['etiqueta' => 'Oculto', 'url' => '/b', 'orden' => 1, 'is_visible' => false]);

        $etiquetas = collect($this->getJson('/api/v1/menu')->json('data'))->pluck('etiqueta');

        $this->assertContains('Visible', $etiquetas);
        $this->assertNotContains('Oculto', $etiquetas);
    }
}

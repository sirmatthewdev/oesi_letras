<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\OesiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Vista previa de borradores.
 *
 * Lo que se prueba aquí no es que la vista previa se vea bonita: es que un
 * borrador **no se escape**. La función existe para enseñar contenido sin
 * publicar, así que cada camino que lo entrega tiene que exigir credencial, y
 * el que no la trae tiene que recibir exactamente lo mismo que un desconocido.
 *
 * El borrador es la propia página, devuelta a borrador desde el panel.
 */
class VistaPreviaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(OesiSeeder::class);

        // Entra publicada; aquí se prueba el camino del borrador, al que la
        // oficina puede volver desde el panel.
        \App\Models\OesiSetting::actual()->update(['estado' => 'borrador']);
    }

    public function test_la_api_no_entrega_el_borrador_sin_token(): void
    {
        config(['sitio.vista_previa.token' => 'el-token-bueno']);

        $this->getJson('/api/v1/oesi')->assertOk()->assertJsonPath('data', null);
    }

    public function test_la_api_entrega_el_borrador_con_el_token(): void
    {
        config(['sitio.vista_previa.token' => 'el-token-bueno']);

        $this->withHeader('X-Vista-Previa', 'el-token-bueno')
            ->getJson('/api/v1/oesi')
            ->assertOk()
            ->assertJsonPath('data.estado', 'borrador');
    }

    public function test_un_token_equivocado_no_abre_nada(): void
    {
        config(['sitio.vista_previa.token' => 'el-token-bueno']);

        $this->withHeader('X-Vista-Previa', 'el-token-malo')
            ->getJson('/api/v1/oesi')
            ->assertJsonPath('data', null);
    }

    /**
     * La guarda que más importa: una instalación recién hecha trae el token
     * vacío. Sin esta comprobación, mandar la cabecera vacía —o cualquier
     * cabecera, si la comparación fuera laxa— abriría el borrador.
     */
    public function test_sin_token_configurado_la_vista_previa_esta_cerrada(): void
    {
        config(['sitio.vista_previa.token' => '']);

        $this->withHeader('X-Vista-Previa', '')->getJson('/api/v1/oesi')->assertJsonPath('data', null);
        $this->withHeader('X-Vista-Previa', 'lo-que-sea')->getJson('/api/v1/oesi')->assertJsonPath('data', null);
    }

    public function test_la_respuesta_con_borradores_no_se_cachea(): void
    {
        config(['sitio.vista_previa.token' => 'el-token-bueno']);

        $this->withHeader('X-Vista-Previa', 'el-token-bueno')
            ->getJson('/api/v1/oesi')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_las_rutas_de_vista_previa_exigen_sesion(): void
    {
        $this->get('/gestion/vista-previa')->assertRedirect('/login');
        $this->get('/gestion/vista-previa/estado')->assertRedirect('/login');
        $this->get('/gestion/vista-previa/sitio/')->assertRedirect('/login');
    }

    /**
     * Sin configurar, la pantalla lo dice en vez de quedarse esperando un
     * servicio que nunca va a contestar.
     */
    public function test_sin_configurar_responde_503_a_quien_ha_entrado(): void
    {
        config(['sitio.vista_previa.token' => '', 'sitio.reconstruccion.token' => '']);

        // Con rol de admin: el grupo lleva `isAdmin`, y sin el rol la
        // peticion se va en un 302 antes de llegar al controlador.
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/gestion/vista-previa')
            ->assertStatus(503);
    }

    public function test_configurada_encarga_el_render_y_muestra_la_espera(): void
    {
        Http::fake(['*' => Http::response(['construyendo' => true], 202)]);
        config([
            'sitio.vista_previa.token' => 'el-token-bueno',
            'sitio.reconstruccion.token' => 'un-token',
            'sitio.vista_previa.url' => 'http://build:4322',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/gestion/vista-previa')
            ->assertOk()
            ->assertSee('el sitio de la OESI');

        Http::assertSent(fn ($p) => $p->url() === 'http://build:4322/vista-previa' && $p->method() === 'POST');
    }

    /**
     * El sitio tiene varias páginas: un enlace interno que saliera tal cual
     * llevaría desde la vista previa al sitio publicado, que no tiene el
     * borrador. Los externos y los de protocolo relativo no se tocan.
     */
    public function test_los_enlaces_entre_paginas_se_quedan_en_la_vista_previa(): void
    {
        Http::fake(['*' => Http::response(
            '<a href="/cronograma">a</a><a href="/">b</a><a href="//cdn.example/x">c</a><a href="https://forms.gle/x">d</a><link href="/_astro/x.css">',
            200,
            ['Content-Type' => 'text/html; charset=utf-8'],
        )]);
        config([
            'sitio.vista_previa.token' => 'el-token-bueno',
            'sitio.reconstruccion.token' => 'un-token',
            'sitio.vista_previa.url' => 'http://build:4322',
        ]);

        $base = route('gestion.vista-previa.archivo');
        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/gestion/vista-previa/sitio/')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('href="' . $base . '/cronograma"', $html);
        $this->assertStringContainsString('href="' . $base . '/"', $html);
        $this->assertStringContainsString('href="' . $base . '/_astro/x.css"', $html);
        $this->assertStringContainsString('href="//cdn.example/x"', $html);
        $this->assertStringContainsString('href="https://forms.gle/x"', $html);
    }
}

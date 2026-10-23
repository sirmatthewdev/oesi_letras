<?php

namespace Tests\Feature;

use App\Jobs\ReconstruirSitio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Cierra el ciclo editorial: publicar en el panel reconstruye el sitio.
 *
 * Sin esto, editar y ver el resultado dejaron de ser el mismo paso al separar
 * el frontend — el coste que la propuesta advertía y que había que pagar.
 */
class ReconstruccionDelSitioTest extends TestCase
{
    use RefreshDatabase;

    public function test_pide_la_reconstruccion_con_el_token(): void
    {
        Http::fake(['*' => Http::response(['mensaje' => 'ok'], 202)]);
        config([
            'sitio.reconstruccion.url' => 'http://build:4322/reconstruir',
            'sitio.reconstruccion.token' => 'un-token',
        ]);

        (new ReconstruirSitio())->handle();

        Http::assertSent(fn (Request $p) => $p->url() === 'http://build:4322/reconstruir'
            && $p->method() === 'POST'
            && $p->hasHeader('Authorization', 'Bearer un-token'));
    }

    public function test_sin_url_configurada_no_hace_nada(): void
    {
        Http::fake();
        // Una instalación que todavía sirve el sitio con Blade no tiene nada
        // que reconstruir: no es un fallo, es que no aplica.
        config(['sitio.reconstruccion.url' => '']);

        (new ReconstruirSitio())->handle();

        Http::assertNothingSent();
    }

    public function test_sin_token_avisa_y_no_llama(): void
    {
        Http::fake();
        Log::spy();
        config([
            'sitio.reconstruccion.url' => 'http://build:4322/reconstruir',
            'sitio.reconstruccion.token' => '',
        ]);

        (new ReconstruirSitio())->handle();

        Http::assertNothingSent();
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_un_fallo_del_servicio_se_relanza_para_reintentar(): void
    {
        Http::fake(['*' => Http::response('sin arrancar', 503)]);
        config([
            'sitio.reconstruccion.url' => 'http://build:4322/reconstruir',
            'sitio.reconstruccion.token' => 'un-token',
        ]);

        // Un servicio de build que aún no ha levantado es justo el caso que el
        // reintento resuelve.
        $this->expectException(\RuntimeException::class);

        (new ReconstruirSitio())->handle();
    }

    public function test_el_trabajo_es_unico_para_agrupar_rafagas(): void
    {
        $job = new ReconstruirSitio();

        // Quien edita guarda seis veces seguidas; sin esto serían seis builds.
        $this->assertSame('reconstruir-sitio', $job->uniqueId());
    }
}

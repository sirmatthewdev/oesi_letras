<?php

namespace Tests\Feature;

use App\Filament\Widgets\Dispositivos;
use App\Filament\Widgets\EstadoDelSitio;
use App\Filament\Widgets\PaginasMasVistas;
use App\Filament\Widgets\Procedencia;
use App\Filament\Widgets\ResumenVisitas;
use App\Filament\Widgets\VisitasPorDia;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El contador de visitas del sitio público y las analíticas del panel.
 *
 * Lo que se guarda aquí es que el endpoint —la única escritura pública de la
 * API— no acepta más de lo que debe, y que el inicio del panel se pinta con y
 * sin datos.
 */
class VisitasTest extends TestCase
{
    use RefreshDatabase;

    private const NAVEGADOR = 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/130 Mobile Safari/537.36';

    private function visitar(array $datos, string $agente = self::NAVEGADOR)
    {
        return $this->withHeader('User-Agent', $agente)->postJson('/api/v1/visita', $datos);
    }

    private function total(string $dimension, string $valor): int
    {
        return (int) Visita::where(compact('dimension', 'valor'))->sum('total');
    }

    public function test_una_entrada_suma_pagina_dispositivo_sesion_y_procedencia(): void
    {
        $this->visitar([
            'ruta' => '/cronograma/',
            'ancho' => 390,
            'referente' => 'https://www.google.com/search?q=oesi',
            'nueva' => true,
        ])->assertNoContent();

        $this->assertSame(1, $this->total(Visita::PAGINA, '/cronograma'));
        $this->assertSame(1, $this->total(Visita::DISPOSITIVO, 'movil'));
        $this->assertSame(1, $this->total(Visita::SESION, 'total'));
        // Solo el dominio, sin `www.` ni la búsqueda.
        $this->assertSame(1, $this->total(Visita::REFERENTE, 'google.com'));
    }

    public function test_navegar_dentro_del_sitio_suma_la_pagina_pero_no_otra_entrada(): void
    {
        $this->visitar(['ruta' => '/', 'ancho' => 1440, 'nueva' => true]);
        $this->visitar(['ruta' => '/examen', 'ancho' => 1440, 'referente' => null, 'nueva' => false]);
        $this->visitar(['ruta' => '/', 'ancho' => 1440, 'nueva' => false]);

        $this->assertSame(2, $this->total(Visita::PAGINA, '/'));
        $this->assertSame(1, $this->total(Visita::PAGINA, '/examen'));
        $this->assertSame(1, $this->total(Visita::SESION, 'total'));
        $this->assertSame(1, $this->total(Visita::REFERENTE, 'directo'));
        $this->assertSame(3, $this->total(Visita::DISPOSITIVO, 'escritorio'));
    }

    public function test_llegar_desde_el_propio_sitio_cuenta_como_directo(): void
    {
        $this->visitar(['ruta' => '/', 'referente' => url('/preguntas'), 'nueva' => true]);

        $this->assertSame(1, $this->total(Visita::REFERENTE, 'directo'));
        $this->assertSame(1, Visita::where('dimension', Visita::REFERENTE)->count());
    }

    public function test_los_robots_no_cuentan(): void
    {
        $this->visitar(['ruta' => '/', 'nueva' => true], 'Mozilla/5.0 (compatible; Googlebot/2.1)')
            ->assertNoContent();

        $this->assertSame(0, Visita::count());
    }

    public function test_una_ruta_que_no_tiene_forma_de_ruta_se_rechaza(): void
    {
        foreach (['cronograma', '/<script>', '/' . str_repeat('a', 120), 'https://otro.com/'] as $ruta) {
            $this->visitar(['ruta' => $ruta])->assertUnprocessable();
        }

        $this->assertSame(0, Visita::count());
    }

    public function test_el_endpoint_tiene_limite_de_peticiones(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->visitar(['ruta' => '/'])->assertNoContent();
        }

        $this->visitar(['ruta' => '/'])->assertTooManyRequests();
    }

    public function test_la_serie_diaria_rellena_los_dias_sin_visitas(): void
    {
        Visita::create(['fecha' => now()->subDays(2)->toDateString(), 'dimension' => Visita::PAGINA, 'valor' => '/', 'total' => 5]);

        $serie = Visita::porDia(Visita::PAGINA, 7);

        $this->assertCount(7, $serie);
        $this->assertSame(5, $serie[now()->subDays(2)->toDateString()]);
        $this->assertSame(0, $serie[now()->toDateString()]);
    }

    public function test_el_inicio_del_panel_muestra_las_analiticas_sin_datos(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/panel')
            ->assertOk()
            ->assertSee('Inicio')
            ->assertSee('Visitas al sitio público');
    }

    /**
     * Los widgets se cargan aparte de la página (lazy), así que el GET de
     * arriba no los pinta: se montan uno a uno, con y sin datos.
     */
    public function test_cada_widget_se_pinta_con_y_sin_datos(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $widgets = [
            ResumenVisitas::class => 'Páginas vistas hoy',
            VisitasPorDia::class => 'Visitas por día',
            PaginasMasVistas::class => 'Páginas más vistas',
            Procedencia::class => 'Procedencia',
            Dispositivos::class => 'Dispositivos',
            EstadoDelSitio::class => 'Trabajos fallidos',
        ];

        foreach ($widgets as $widget => $texto) {
            Livewire::test($widget)->assertOk()->assertSee($texto);
        }

        $this->visitar(['ruta' => '/cronograma', 'ancho' => 390, 'referente' => 'https://facebook.com/', 'nueva' => true]);

        foreach ($widgets as $widget => $texto) {
            Livewire::test($widget)->assertOk()->assertSee($texto);
        }

        Livewire::test(ResumenVisitas::class)->assertSee('Sin datos de ayer');
        Livewire::test(VisitasPorDia::class)->set('filter', '7')->assertOk();
    }
}

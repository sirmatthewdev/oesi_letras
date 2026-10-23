<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\OesiSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Una instalación limpia trae lo de la OESI, y solo lo de la OESI.
 *
 * Este proyecto nació como copia del sitio del CERSEU, que a su vez nació del
 * de la Unidad de Posgrado, y allí el contenido de la unidad anterior se coló
 * cinco veces por sitios distintos —un seeder, una migración, el respaldo de
 * un controlador—. Esta prueba monta la base como un despliegue nuevo y lo
 * comprueba en las dos direcciones: que no aparece la oferta del CERSEU y que
 * sí llega el contenido entero de la oficina.
 *
 * «Maestría», «bachiller» o «Doctorado» SÍ son legítimos aquí: son el público
 * del examen y su tarifario cobra distinto a cada uno.
 */
class InstalacionLimpiaTest extends TestCase
{
    use RefreshDatabase;

    /** Lo que delata contenido del CERSEU colado en este sitio. */
    private const VOCABULARIO_AJENO = [
        'Centro de Responsabilidad Social',
        'talleres',
        'especializaciones',
        'plana docente',
        'cerseu.letras@',
    ];

    public function test_solo_quedan_las_tablas_de_la_oesi(): void
    {
        $tablas = collect(Schema::getTableListing())
            ->map(fn (string $t) => str_contains($t, '.') ? substr($t, strrpos($t, '.') + 1) : $t);

        foreach (['programas', 'docentes', 'site_settings', 'admision_settings', 'leads', 'content_pages'] as $ajena) {
            $this->assertNotContains($ajena, $tablas, "La tabla «{$ajena}» es del CERSEU.");
        }

        $this->assertContains('oesi_settings', $tablas);
    }

    public function test_los_seeders_no_publican_contenido_del_cerseu(): void
    {
        $this->seed();

        $hallazgos = [];

        foreach (['oesi_settings', 'menu_items'] as $tabla) {
            foreach (DB::table($tabla)->get() as $fila) {
                foreach ((array) $fila as $columna => $valor) {
                    foreach (self::VOCABULARIO_AJENO as $palabra) {
                        if (mb_stripos((string) $valor, $palabra) !== false) {
                            $hallazgos[] = "{$tabla}.{$columna} contiene «{$palabra}»";
                        }
                    }
                }
            }
        }

        $this->assertSame([], $hallazgos, implode("\n", $hallazgos));
    }

    public function test_una_instalacion_limpia_trae_la_oficina_entera_y_publicada(): void
    {
        $this->seed();

        $oesi = OesiSetting::actual();

        $this->assertNotNull($oesi, 'No se sembró la OESI.');
        // Publicada y con las cifras a la vista: la oficina lo validó y así
        // lo dice el fichero de datos.
        $this->assertSame(OesiSetting::ESTADO_PUBLICADO, $oesi->estado);
        $this->assertTrue($oesi->estadisticas_verificadas);
        $this->assertCount(12, $oesi->faqs);
        $this->assertCount(5, $oesi->tarifario);
        $this->assertSame(1, OesiSetting::count());
    }

    public function test_trae_un_administrador_y_el_menu(): void
    {
        $this->seed();

        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertSame('examensuficiencia.flch@unmsm.edu.pe', User::first()->email);

        // Las secciones de la página, enlazadas por su ancla.
        $this->assertContains('/inscripcion', MenuItem::all()->map->enlace->filter()->all());
    }
}

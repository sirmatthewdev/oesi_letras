<?php

namespace Tests\Feature;

use App\Filament\Pages\OficinaDeSuficiencia;
use App\Models\OesiSetting;
use App\Models\User;
use Database\Seeders\OesiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * La Oficina de Examen de Suficiencia en Idiomas: el contenido del sitio.
 *
 * Tres cosas que vigilar, y por eso tres bloques:
 *
 * 1. **Que la migración de la landing llegó entera.** Precios, códigos de San
 *    Market y formularios se comparan uno a uno con la fuente: un dígito
 *    cambiado en un enlace de pago es un pago que va a otro concepto, y no
 *    lo delata ningún error.
 * 2. **Que no se publica antes de revisarse.** Entra en borrador y con las
 *    cifras sin verificar; la API no la sirve al sitio publicado hasta que
 *    alguien la publique desde el panel, y las cifras ni siquiera entonces.
 * 3. **Que el panel la edita de verdad**: cada campo apunta a una columna, y
 *    lo que se guarda es lo que sirve la API.
 */
class OesiTest extends TestCase
{
    use RefreshDatabase;

    /** Tarifario de la fuente: concepto, importe y código de San Market. */
    private const TARIFARIO = [
        ['Examen Ordinario (Presencial o Virtual)', 200.0, '10ca399c-2e8c-4149-a3f6-5a4eea239a7a'],
        ['Examen Extraordinario (Presencial o Virtual)', 300.0, '60c6e0d4-e89e-4b9c-b3b4-5cf28cbba845'],
        ['Examen de Suficiencia Presencial para Bachiller', 100.0, '40fba915-602f-4108-a195-c735b1ef124b'],
        ['Examen de Suficiencia Presencial para Bachiller (FLCH)', 50.0, 'f3d8cd22-201a-4743-9103-4f9e32b27737'],
        ['Duplicado de Certificado', 100.0, '72fc7639-34f5-4193-a774-34c1d65da72d'],
    ];

    /** Los cinco costos de inscripción, en el orden de la fuente. */
    private const COSTOS = [300.0, 200.0, 300.0, 100.0, 50.0];

    private function sembrar(): OesiSetting
    {
        $this->seed(OesiSeeder::class);

        return OesiSetting::actual();
    }

    /**
     * La oficina devuelve la página a borrador desde el panel. Entra publicada
     * (lo validó la oficina), pero el camino del borrador sigue existiendo y
     * es el que hay que vigilar: lo que no está publicado no se escapa.
     */
    private function enBorrador(): OesiSetting
    {
        $oesi = $this->sembrar();
        $oesi->update(['estado' => OesiSetting::ESTADO_BORRADOR, 'estadisticas_verificadas' => false]);

        return $oesi;
    }

    private function publicar(array $ademas = []): OesiSetting
    {
        $oesi = $this->sembrar();
        $oesi->update(['estado' => OesiSetting::ESTADO_PUBLICADO, ...$ademas]);

        return $oesi;
    }

    // ── 1. La migración llegó entera ────────────────────────────────────

    public function test_la_migracion_trae_las_siete_secciones_completas(): void
    {
        $o = $this->sembrar();

        $this->assertSame('Oficina de Examen de Suficiencia en Idiomas', $o->nombre);
        $this->assertCount(12, $o->faqs, 'Faltan preguntas frecuentes.');
        $this->assertCount(4, $o->habilidades);
        $this->assertCount(4, $o->modalidades);
        $this->assertCount(7, $o->examen_destinatarios);
        $this->assertCount(3, $o->escala);
        $this->assertCount(4, $o->vigencia);
        $this->assertCount(3, $o->documentos);
        $this->assertCount(4, $o->estadisticas);

        foreach ($o->faqs as $faq) {
            $this->assertNotEmpty($faq['pregunta']);
            $this->assertNotEmpty($faq['respuesta'], "«{$faq['pregunta']}» no tiene respuesta.");
        }
    }

    public function test_la_escala_conserva_los_rangos_y_notas_de_la_fuente(): void
    {
        $escala = $this->sembrar()->escala;

        $this->assertSame(['12–14', '15–17', '18–20'], array_column($escala, 'rango'));
        $this->assertSame(
            ['A1' => '12', 'A2' => '13–14', 'B1' => '15', 'B2' => '16–17', 'C1' => '18', 'C2' => '19–20'],
            collect($escala)->flatMap(fn ($n) => $n['subniveles'])->pluck('nota', 'codigo')->all()
        );
    }

    public function test_cada_precio_y_cada_enlace_de_pago_es_el_de_la_fuente(): void
    {
        $tarifario = $this->sembrar()->tarifario;

        $this->assertCount(count(self::TARIFARIO), $tarifario);

        foreach (self::TARIFARIO as $i => [$concepto, $monto, $codigo]) {
            $this->assertSame($concepto, $tarifario[$i]['concepto']);
            $this->assertEquals($monto, $tarifario[$i]['monto']);
            $this->assertSame("https://sanmarket.unmsm.edu.pe/#/catalogo/{$codigo}", $tarifario[$i]['url_pago']);
        }

        $this->assertEquals(self::COSTOS, array_column($this->sembrar()->costos, 'monto'));
    }

    public function test_los_cinco_formularios_siguen_separados_por_finalidad(): void
    {
        $o = $this->sembrar();

        // Los del cronograma y los de inscripción NO son los mismos: hay dos de
        // posgrado distintos en la fuente, y unificarlos sin confirmarlo con la
        // oficina sería mandar a alguien al formulario equivocado.
        $this->assertSame(
            ['Pregrado' => 'https://forms.gle/1w1ySuaFFfbPb1Ff9', 'Posgrado' => 'https://forms.gle/QZguJrD9xmQd5DqYA'],
            array_column($o->cronograma_formularios, 'url', 'nombre')
        );
        $this->assertSame(
            [
                'Formulario para posgrado' => 'https://forms.gle/otuxExVEdg8NX4M89',
                'Formulario para bachilleres' => 'https://forms.gle/tL38aMvUtR7XzAnbA',
                'Solicitud de duplicado' => 'https://forms.gle/stPNDN1qJTkwrHcNA',
            ],
            array_column($o->formularios, 'url', 'nombre')
        );
    }

    public function test_cada_clave_del_fichero_de_datos_es_una_columna(): void
    {
        $datos = json_decode(file_get_contents(database_path('seeders/data/oesi.json')), true);
        $columnas = Schema::getColumnListing('oesi_settings');

        foreach (array_keys($datos) as $clave) {
            if (str_starts_with($clave, '_')) {
                continue;
            }

            $this->assertContains($clave, $columnas, "«{$clave}» del fichero de datos no tiene columna: no se cargaría.");
        }

        // Y al revés: ninguna columna de contenido se queda sin su dato.
        $sinDato = array_diff($columnas, array_keys($datos), ['id', 'estado', 'created_at', 'updated_at']);
        $this->assertSame([], array_values($sinDato), 'Columnas sin contenido en data/oesi.json.');
    }

    // ── 2. Publicación ──────────────────────────────────────────────────

    public function test_entra_publicada_y_con_las_cifras_verificadas_porque_lo_dice_el_fichero(): void
    {
        // La oficina validó contenido y cifras el 20-10-2026. Lo dice el
        // fichero de datos, no un valor por defecto del seeder.
        $o = $this->sembrar();

        $this->assertSame(OesiSetting::ESTADO_PUBLICADO, $o->estado);
        $this->assertTrue($o->estadisticas_verificadas);

        $this->getJson('/api/v1/oesi')
            ->assertOk()
            ->assertJsonPath('data.estado', 'publicado')
            ->assertJsonPath('data.estadisticas.verificadas', true)
            ->assertJsonCount(4, 'data.estadisticas.cifras');
    }

    public function test_en_borrador_la_api_no_la_sirve(): void
    {
        $this->enBorrador();

        $this->getJson('/api/v1/oesi')->assertOk()->assertJsonPath('data', null);
    }

    public function test_la_vista_previa_ve_el_borrador_con_sus_cifras_y_no_se_cachea(): void
    {
        $this->enBorrador();
        config(['sitio.vista_previa.token' => 'el-token']);

        $respuesta = $this->withHeader('X-Vista-Previa', 'el-token')->getJson('/api/v1/oesi');

        $respuesta->assertOk()
            ->assertJsonPath('data.estado', 'borrador')
            ->assertJsonPath('data.estadisticas.verificadas', false)
            ->assertJsonCount(4, 'data.estadisticas.cifras');

        $this->assertStringContainsString('no-store', $respuesta->headers->get('Cache-Control'));
    }

    public function test_publicada_sirve_la_pagina_pero_no_las_cifras_sin_verificar(): void
    {
        // Si alguien desmarca «verificadas» en el panel, las cifras dejan de
        // salir aunque la página siga publicada.
        $this->publicar(['estadisticas_verificadas' => false]);

        $this->getJson('/api/v1/oesi')
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Oficina de Examen de Suficiencia en Idiomas')
            ->assertJsonPath('data.estadisticas.verificadas', false)
            ->assertJsonPath('data.estadisticas.cifras', [])
            ->assertJsonCount(12, 'data.faqs.items')
            ->assertJsonCount(5, 'data.inscripcion.tarifario');
    }

    public function test_las_cifras_salen_cuando_alguien_las_verifica(): void
    {
        $this->publicar(['estadisticas_verificadas' => true]);

        $this->getJson('/api/v1/oesi')
            ->assertJsonPath('data.estadisticas.cifras.0.valor', 2500)
            ->assertJsonPath('data.estadisticas.cifras.0.sufijo', '+');
    }

    // ── El contrato con el sitio ────────────────────────────────────────

    public function test_el_whatsapp_se_compone_del_numero_y_el_mensaje(): void
    {
        $this->publicar();

        $this->getJson('/api/v1/oesi')
            ->assertJsonPath('data.contacto.whatsapp.enlace', 'https://wa.me/51914033108?text=Hola%2C%20quiero%20consultar%20sobre%20el%20examen%20de%20suficiencia')
            // El dato que la landing anterior intentaba copiar sin tenerlo.
            ->assertJsonPath('data.contacto.whatsapp.visible', '+51 914 033 108')
            ->assertJsonPath('data.contacto.email', 'examensuficiencia.flch@unmsm.edu.pe')
            // El del curso es otro número, y lleva su propio mensaje.
            ->assertJsonPath('data.curso.whatsapp.enlace', 'https://wa.me/51982086196?text=Hola%20quisiera%20informaci%C3%B3n');
    }

    public function test_un_numero_local_recibe_el_codigo_de_pais(): void
    {
        $this->assertSame('https://wa.me/51914033108', OesiSetting::whatsapp('914 033 108')['enlace']);
        $this->assertNull(OesiSetting::whatsapp(''));
    }

    public function test_las_preguntas_salen_con_su_tema_y_los_temas_con_su_orden(): void
    {
        $this->publicar();

        $respuesta = $this->getJson('/api/v1/oesi')
            ->assertJsonPath('data.faqs.temas.0', ['clave' => 'fechas', 'etiqueta' => 'Fechas y modalidades'])
            ->assertJsonCount(count(OesiSetting::TEMAS_FAQ), 'data.faqs.temas')
            ->assertJsonPath('data.faqs.items.0.categoria', 'fechas');

        // Las doce tienen tema en la migración: ninguna cae a «Otras».
        $this->assertNotContains(null, array_column($respuesta->json('data.faqs.items'), 'categoria'));
    }

    public function test_un_tema_que_ya_no_existe_cuenta_como_ninguno(): void
    {
        $this->publicar(['faqs' => [['pregunta' => '¿Una?', 'respuesta' => 'Sí.', 'categoria' => 'retirado']]]);

        $this->getJson('/api/v1/oesi')->assertJsonPath('data.faqs.items.0.categoria', null);
    }

    public function test_las_redes_salen_como_clave_y_enlace(): void
    {
        $this->publicar();

        $this->getJson('/api/v1/oesi')->assertJsonPath('data.redes', [
            'facebook' => 'https://www.facebook.com/share/1ASMCppqmD/',
            'instagram' => 'https://www.instagram.com/oesidiomas.unmsm/',
            'whatsapp' => 'https://whatsapp.com/channel/0029Vb7DK3gDjiObIzW5Cz12',
        ]);
    }

    public function test_las_filas_del_repetidor_salen_como_lista_aunque_lleven_claves(): void
    {
        // El repetidor del panel puede guardar sus filas con claves UUID. Si la
        // API las sirviera tal cual, el JSON sería un objeto y el sitio no
        // podría recorrerlo.
        $this->publicar([
            'faqs' => ['a1b2' => ['pregunta' => '¿Uno?', 'respuesta' => 'Sí.', 'lista' => ['x' => ['texto' => 'Viñeta']]]],
            'documentos' => ['k' => ['texto' => 'Recibo'], 'j' => ['texto' => '  ']],
        ]);

        $this->getJson('/api/v1/oesi')
            ->assertJsonPath('data.faqs.items.0.pregunta', '¿Uno?')
            ->assertJsonPath('data.faqs.items.0.lista', ['Viñeta'])
            ->assertJsonPath('data.inscripcion.documentos', ['Recibo']);
    }

    // ── El seeder no pisa lo editado ────────────────────────────────────

    public function test_sembrar_dos_veces_no_pisa_lo_que_edito_la_oficina(): void
    {
        $this->sembrar()->update(['email' => 'otro@unmsm.edu.pe']);

        $this->seed(OesiSeeder::class);

        $this->assertSame(1, OesiSetting::count());
        $this->assertSame('otro@unmsm.edu.pe', OesiSetting::actual()->email);
    }

    public function test_una_fila_vacia_creada_por_el_panel_no_impide_sembrar(): void
    {
        // Abrir la página del panel antes de sembrar crea la fila en blanco.
        OesiSetting::query()->create([]);

        $this->seed(OesiSeeder::class);

        $this->assertSame(1, OesiSetting::count());
        $this->assertCount(12, OesiSetting::actual()->faqs);
    }

    public function test_no_puede_haber_dos_oesi(): void
    {
        $this->sembrar();

        $this->expectException(\LogicException::class);
        OesiSetting::query()->create(['nombre' => 'Otra']);
    }

    // ── 3. El panel la edita ────────────────────────────────────────────

    public function test_el_panel_abre_y_guarda_lo_que_sirve_la_api(): void
    {
        $this->sembrar();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get(OficinaDeSuficiencia::getUrl())->assertOk();

        Livewire::test(OficinaDeSuficiencia::class)
            ->fillForm([
                'estado' => OesiSetting::ESTADO_PUBLICADO,
                'cronograma_imagen' => 'https://letras.unmsm.edu.pe/wp-content/uploads/2026/10/CRONOGRAMA-OESI-OCT.png',
                'cronograma_imagen_alt' => 'Cronograma de exámenes de octubre',
            ])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $this->getJson('/api/v1/oesi')
            ->assertJsonPath('data.cronograma.imagen.url', 'https://letras.unmsm.edu.pe/wp-content/uploads/2026/10/CRONOGRAMA-OESI-OCT.png')
            // Lo que no se tocó sigue como estaba.
            ->assertJsonCount(12, 'data.faqs.items')
            ->assertJsonPath('data.inscripcion.tarifario.4.monto', 100);
    }

    public function test_el_panel_rechaza_una_imagen_sin_descripcion(): void
    {
        $this->sembrar();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(OficinaDeSuficiencia::class)
            ->fillForm(['cronograma_imagen_alt' => ''])
            ->call('guardar')
            ->assertHasFormErrors(['cronograma_imagen_alt']);
    }

    public function test_todo_campo_del_panel_es_una_columna(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $columnas = Schema::getColumnListing('oesi_settings');

        $pagina = Livewire::test(OficinaDeSuficiencia::class)->instance();

        $nombres = [];
        $recorrer = function ($componentes) use (&$recorrer, &$nombres): void {
            foreach ($componentes as $c) {
                if ($c instanceof \Filament\Forms\Components\Field) {
                    $nombres[] = $c->getName();

                    if ($c instanceof \Filament\Forms\Components\Repeater) {
                        continue;
                    }
                }

                if (method_exists($c, 'getDefaultChildComponents')) {
                    $recorrer($c->getDefaultChildComponents());
                }
            }
        };
        $recorrer($pagina->form->getComponents());

        $this->assertNotEmpty($nombres);

        foreach (array_unique($nombres) as $campo) {
            $this->assertContains($campo, $columnas, "El campo «{$campo}» no es una columna de oesi_settings.");
        }
    }
}

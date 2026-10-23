<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Panel de administración en Filament.
 *
 * Lo que se guarda aquí es el control de acceso. Filament trae su propio
 * criterio por defecto —deja entrar a cualquiera en entorno local y a nadie
 * fuera de él— y ese respaldo se aplica en silencio si `canAccessPanel()` no
 * está: un panel abierto en desarrollo no lo nota nadie hasta que alguien
 * levanta el entorno con otra bandera.
 */
class PanelFilamentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_sin_sesion_el_panel_manda_al_login(): void
    {
        $this->get('/panel')->assertRedirect('/login');
        $this->get('/panel/oficina-de-suficiencia')->assertRedirect('/login');
    }

    public function test_quien_no_es_admin_no_entra_aunque_tenga_sesion(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get('/panel')
            ->assertForbidden();
    }

    /**
     * La misma comprobación en entorno local, que es la que de verdad guarda
     * algo: ahí el respaldo de Filament deja entrar a cualquiera con sesión, y
     * como las pruebas nunca corren en `local`, el caso peligroso no lo veía
     * nadie. Se fuerza tocando `config('app.env')`, que es lo que Filament mira.
     */
    public function test_en_local_tampoco_entra_quien_no_es_admin(): void
    {
        config(['app.env' => 'local']);

        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get('/panel')
            ->assertForbidden();
    }

    /** Administrador Y cuenta activa: desactivar a alguien tiene que cerrarle el panel. */
    public function test_solo_entra_quien_es_admin_y_tiene_la_cuenta_activa(): void
    {
        $admin = $this->admin();
        $normal = User::factory()->create(['role' => 'user']);
        $desactivado = User::factory()->create(['role' => 'admin', 'is_active' => false]);

        $panel = app(\Filament\Panel::class);

        $this->assertTrue($admin->canAccessPanel($panel));
        $this->assertFalse($normal->canAccessPanel($panel));
        $this->assertFalse($desactivado->canAccessPanel($panel), 'Una cuenta dada de baja no debe entrar.');
    }

    /**
     * Los recursos abren, en listado y en creación: un campo mal declarado no
     * falla al arrancar la aplicación, sino al pintar esa pantalla.
     *
     * @return list<array{string}>
     */
    public static function recursos(): array
    {
        return [['menu-items'], ['users']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('recursos')]
    public function test_cada_recurso_abre_su_listado_y_su_formulario(string $recurso): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get("/panel/{$recurso}")->assertOk();
        $this->actingAs($admin)->get("/panel/{$recurso}/create")->assertOk();
    }

    public function test_el_contenido_del_sitio_abre(): void
    {
        $this->actingAs($this->admin())
            ->get('/panel/oficina-de-suficiencia')
            ->assertOk()
            ->assertSee('Oficina de Examen de Suficiencia en Idiomas');
    }

    /**
     * El panel es solo de la OESI: no queda ningún recurso de la oferta de la
     * que salió este proyecto.
     */
    public function test_no_quedan_pantallas_de_otra_unidad(): void
    {
        $admin = $this->admin();

        foreach (['programas', 'docentes', 'admision-settings', 'leads', 'configuracion-del-sitio'] as $ajena) {
            $this->actingAs($admin)->get("/panel/{$ajena}")->assertNotFound();
        }
    }
}

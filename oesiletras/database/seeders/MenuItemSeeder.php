<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * Menú inicial del sitio de la OESI.
 *
 * Las páginas del sitio, una por tema, y los sitios
 * hermanos de la Facultad. Es andamiaje: a partir de aquí se edita en el panel,
 * en «Menú», y este seeder no vuelve a tocarlo si ya hay elementos.
 */
class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        if (MenuItem::query()->exists()) {
            $this->command?->info('El menú ya tiene elementos; no se toca.');

            return;
        }

        foreach ($this->menu() as $ordenPadre => $padre) {
            $hijos = $padre['hijos'] ?? [];
            unset($padre['hijos']);

            $item = MenuItem::create($padre + ['orden' => $ordenPadre]);

            foreach ($hijos as $ordenHijo => $hijo) {
                MenuItem::create($hijo + [
                    'parent_id' => $item->id,
                    'orden' => $ordenHijo,
                ]);
            }
        }

        MenuItem::clearCache();
    }

    private function menu(): array
    {
        return [
            ['etiqueta' => 'El examen', 'route_name' => 'examen', 'icono' => 'fas-file-alt'],
            ['etiqueta' => 'Modalidades', 'route_name' => 'modalidades', 'icono' => 'fas-laptop'],
            ['etiqueta' => 'Cronograma', 'route_name' => 'cronograma', 'icono' => 'fas-calendar-alt'],
            ['etiqueta' => 'Inscripción', 'route_name' => 'inscripcion', 'icono' => 'fas-user-plus'],
            ['etiqueta' => 'Preguntas frecuentes', 'route_name' => 'preguntas', 'icono' => 'fas-circle-question'],
            [
                'etiqueta' => 'Facultad', 'icono' => 'fas-university',
                'url' => 'https://letras.unmsm.edu.pe',
                'nueva_pestana' => true,
                'hijos' => [
                    ['etiqueta' => 'Web Letras', 'url' => 'https://letras.unmsm.edu.pe/', 'icono' => 'fas-university', 'nueva_pestana' => true],
                    ['etiqueta' => 'CEID', 'url' => 'https://ceidletras.unmsm.edu.pe/', 'icono' => 'fas-language', 'nueva_pestana' => true],
                    ['etiqueta' => 'CERSEU', 'url' => 'https://cerseuletras.unmsm.edu.pe/', 'icono' => 'fas-graduation-cap', 'nueva_pestana' => true],
                    ['etiqueta' => 'Posgrado Letras', 'url' => 'https://posgradoletras.unmsm.edu.pe/', 'icono' => 'fas-graduation-cap', 'nueva_pestana' => true],
                ],
            ],
        ];
    }
}

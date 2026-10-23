<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Instalación inicial del sitio de la OESI.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,

            // El contenido de la oficina, migrado de la landing que tenía en
            // letras.unmsm.edu.pe. Entra en borrador: la portada dice
            // «próximamente» hasta que la oficina lo revise y lo publique.
            OesiSeeder::class,

            // Sin él la cabecera arranca sin navegación.
            MenuItemSeeder::class,
        ]);
    }
}

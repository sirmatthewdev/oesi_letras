<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Usuario administrador inicial.
 *
 * Se usa `firstOrCreate` porque `create` a secas rompía la cadena entera de
 * seeders al reejecutarla —violación de clave única en `users.email`— y los
 * seeders posteriores no llegaban a correr.
 *
 * **La contraseña ya no está escrita aquí.** Estaba, y era «admin123»: en un
 * repositorio, una contraseña escrita es una contraseña publicada, y la cuenta
 * que abre es la de administrador de cualquier entorno sembrado con este
 * archivo. Ahora sale de `ADMIN_PASSWORD`, y si no está, se genera una al vuelo
 * y se imprime una sola vez.
 *
 * No hay valor por defecto a propósito: un defecto conocido es exactamente el
 * problema que se viene a quitar, y uno «temporal» sobrevive al despliegue.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * `env()` y no `config()`: esto corre por consola y no merece una
         * entrada de configuración propia. Lo que sí hay que saber es que
         * después de `php artisan config:cache` Laravel deja de leer el `.env`
         * y esto devuelve vacío — entonces se genera una, que es un desenlace
         * seguro, pero conviene sembrar ANTES de cachear.
         */
        $clave = trim((string) env('ADMIN_PASSWORD', ''));
        $generada = $clave === '';

        if ($generada) {
            $clave = Str::password(20);
        }

        // El buzón de la oficina por defecto: es a donde llega el enlace de
        // «olvidé mi contraseña», y una dirección inventada lo mandaría a
        // ninguna parte. `ADMIN_EMAIL` lo cambia.
        $correo = trim((string) env('ADMIN_EMAIL', '')) ?: 'examensuficiencia.flch@unmsm.edu.pe';

        $admin = User::firstOrCreate(
            ['email' => $correo],
            [
                'name' => 'Administrador',
                // El modelo castea `password` a `hashed`: se guarda cifrada sin
                // pasarla por `Hash::make` aquí.
                'password' => $clave,
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        if (! $admin->wasRecentlyCreated) {
            $this->command?->line('El usuario admin ya existía: no se le toca la contraseña.');

            return;
        }

        if ($generada) {
            $this->command?->warn('Se generó una contraseña para el usuario admin:');
            $this->command?->line("  {$clave}");
            $this->command?->line('Anótala: no vuelve a mostrarse y no queda escrita en ningún sitio.');

            return;
        }

        $this->command?->line('Usuario admin creado con la contraseña de ADMIN_PASSWORD.');
    }
}

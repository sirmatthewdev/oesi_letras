<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Da acceso al panel a un usuario existente.
 *
 * Esta versión añade dos columnas a `users` que Laravel no trae de serie:
 * `role` y `is_active`. Al integrar los usuarios que ya existen en producción,
 * la migración los crea con `role = 'user'`, así que **ninguno podría entrar al
 * panel** hasta promoverlo. Este comando evita tener que tocar la base a mano.
 */
class HacerAdmin extends Command
{
    protected $signature = 'usuario:admin {correo} {--quitar : Retira el acceso en lugar de darlo}';

    protected $description = 'Da (o retira) acceso al panel a un usuario existente';

    public function handle(): int
    {
        $usuario = User::where('email', $this->argument('correo'))->first();

        if (! $usuario) {
            $this->error("No existe ningún usuario con el correo {$this->argument('correo')}.");
            $this->line('Usuarios registrados: ' . User::count() . '. Para verlos: php artisan tinker');

            return self::FAILURE;
        }

        if ($this->option('quitar')) {
            $usuario->update(['role' => 'user']);
            $this->info("{$usuario->email} ya no tiene acceso al panel.");

            return self::SUCCESS;
        }

        $usuario->update(['role' => 'admin', 'is_active' => true]);

        $this->info("{$usuario->email} ({$usuario->name}) ya puede administrar el sitio.");
        $this->line('Administradores activos: ' . User::where('role', 'admin')->where('is_active', true)->count());

        return self::SUCCESS;
    }
}

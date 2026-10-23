<?php

namespace App\Console\Commands;

use App\Models\OesiSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Comprueba que el correo sale de verdad.
 *
 * El sitio no envía correo por su cuenta, pero el panel sí: el enlace de
 * «olvidé mi contraseña» sale por SMTP, y sin transporte configurado nadie
 * puede recuperar su cuenta. Esto lo comprueba sin esperar a que haga falta.
 */
class ProbarCorreo extends Command
{
    protected $signature = 'correo:probar {destino? : A dónde enviar (por defecto, el correo de la oficina)}';

    protected $description = 'Envía un correo de prueba para comprobar la configuración SMTP';

    public function handle(): int
    {
        $destino = $this->argument('destino') ?: OesiSetting::actual()?->email;
        $driver = config('mail.default');

        if (! $destino) {
            $this->error('Sin destino: pásalo como argumento o carga el correo de la oficina en el panel.');

            return self::FAILURE;
        }

        $this->line('Remitente: ' . config('mail.from.name') . ' <' . config('mail.from.address') . '>');
        $this->line('Destino:   ' . $destino);
        $this->line('Transporte: ' . $driver);
        $this->newLine();

        if ($driver === 'log') {
            $this->warn('MAIL_MAILER=log: el correo se escribirá en storage/logs/laravel.log y NO saldrá.');
            $this->line('Pon MAIL_MAILER=smtp con los datos del servidor y ejecuta `php artisan config:clear`.');
            $this->newLine();
        }

        try {
            Mail::raw(
                'Correo de prueba del panel de la OESI. Si lo lees, el transporte SMTP funciona.',
                fn ($mensaje) => $mensaje->to($destino)->subject('Prueba de correo · OESI Letras')
            );
        } catch (\Throwable $e) {
            $this->error('El envío falló: ' . $e->getMessage());
            $this->newLine();
            $this->line('Causas habituales:');
            $this->line('  · Credenciales incorrectas o cuenta sin permiso de envío SMTP.');
            $this->line('  · Puerto bloqueado por el servidor (probar 587 con TLS o 465 con SSL).');
            $this->line('  · Certificados de cURL/OpenSSL sin configurar (ver docs/correo.md).');

            return self::FAILURE;
        }

        if ($driver === 'log') {
            $this->info('Mensaje compuesto y escrito en el log. Revisa storage/logs/laravel.log.');

            return self::SUCCESS;
        }

        $this->info("Correo enviado a {$destino}.");
        $this->line('Si no llega en unos minutos, revisa la carpeta de spam y los registros del servidor.');

        return self::SUCCESS;
    }
}

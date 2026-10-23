<?php

namespace App\Observers;

use App\Jobs\ReconstruirSitio;
use Illuminate\Database\Eloquent\Model;

/**
 * Pide reconstruir el sitio cuando cambia algo que el sitio muestra.
 *
 * Se engancha a los modelos cuyo contenido acaba en el HTML generado, y solo a
 * esos. Un usuario del panel cambia y no se publica en ninguna parte:
 * reconstruir por cada cambio de contraseña sería reconstruir por nada.
 *
 * El trabajo sale con retraso y es único (ver ReconstruirSitio), así que
 * guardar seis veces seguidas provoca una reconstrucción, no seis.
 */
class PublicacionObserver
{
    public function saved(Model $modelo): void
    {
        $this->pedir();
    }

    public function deleted(Model $modelo): void
    {
        $this->pedir();
    }

    public function restored(Model $modelo): void
    {
        $this->pedir();
    }

    private function pedir(): void
    {
        // Durante las pruebas y los seeders no: sembrar la base dispararía una
        // reconstrucción por cada fila insertada.
        if (app()->runningUnitTests() || app()->runningInConsole()) {
            return;
        }

        ReconstruirSitio::dispatch()
            ->delay(now()->addSeconds(config('sitio.reconstruccion.espera_segundos')));
    }
}

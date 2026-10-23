<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Modelos cuyo contenido acaba en el HTML del sitio publico: guardar uno
     * pide reconstruirlo. Un usuario del panel, por ejemplo, no se publica.
     */
    private const MODELOS_PUBLICADOS = [
        \App\Models\OesiSetting::class,
        \App\Models\MenuItem::class,
    ];

    public function boot(): void
    {
        foreach (self::MODELOS_PUBLICADOS as $modelo) {
            $modelo::observe(\App\Observers\PublicacionObserver::class);
        }
    }
}

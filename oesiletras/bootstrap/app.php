<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // La API la consume Astro desde dentro de la red de Docker, donde el
        // host de la peticion es `web`. Sin esto, las URLs absolutas que
        // genera Laravel salen apuntando a ese host interno.
        $middleware->api(append: [
            \App\Http\Middleware\ForzarUrlPublica::class,
        ]);

        $middleware->alias([
            'isAdmin' => \App\Http\Middleware\IsAdmin::class,
        ]);

        // Deja una sola nota de licencia de Font Awesome por página en vez de
        // una por icono (~23 KB de HTML en la portada).
        $middleware->web(append: [
            \App\Http\Middleware\CompactarAtribucionIconos::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

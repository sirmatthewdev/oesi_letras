<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VistaPreviaController;

/*
 * Lo que queda de Laravel en el navegador: el panel y la sesion.
 *
 * El sitio publico no pasa por aqui. Lo sirve Nginx desde el `dist/` que
 * genera Astro contra la API de contenido (routes/api.php), asi que estas
 * rutas son las unicas que devuelven HTML: /login y el flujo de contrasenas,
 * /profile para la cuenta propia, y /gestion para la vista previa.
 *
 * El panel es Filament, en /panel, y sus rutas las registra su PanelProvider.
 */

/*
 * La vista previa de borradores: sirve HTML generado por el build, con rutas
 * con barras dentro, y no encaja en una pagina de Filament.
 *
 * Prefijo propio y no /panel: ahi manda Filament, y una ruta nuestra colgada de
 * su espacio se rompe el dia que Filament reclame ese slug. Detras de la sesion:
 * sirve contenido sin publicar.
 */
Route::middleware(['auth', 'isAdmin'])->prefix('gestion')->name('gestion.')->group(function () {
    Route::get('vista-previa', [VistaPreviaController::class, 'sitio'])
        ->name('vista-previa.sitio');
    Route::get('vista-previa/estado', [VistaPreviaController::class, 'estado'])
        ->name('vista-previa.estado');
    // `where` con comodin: una ruta de vista previa trae barras
    // («_astro/algo.css»), y sin eso Laravel corta en la primera.
    Route::get('vista-previa/sitio/{ruta?}', [VistaPreviaController::class, 'archivo'])
        ->where('ruta', '.*')
        ->name('vista-previa.archivo');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

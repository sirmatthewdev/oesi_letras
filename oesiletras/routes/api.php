<?php

use App\Http\Controllers\Api\OesiApiController;
use App\Http\Controllers\Api\SitioApiController;
use Illuminate\Support\Facades\Route;

/*
 * API de contenido, de solo lectura.
 *
 * El sitio publico en Astro se construye contra este contrato y no contra la
 * base de datos. El prefijo /v1 no es ceremonia: cuando haya que cambiar la
 * forma de una respuesta, un sitio ya desplegado tiene que poder seguir
 * pidiendo la anterior mientras se reconstruye.
 */
Route::prefix('v1')->group(function () {
    // Identidad y navegacion: lo que pintan la cabecera y el pie.
    Route::get('/sitio', [SitioApiController::class, 'configuracion']);
    Route::get('/menu', [SitioApiController::class, 'menu']);

    // La pagina de la oficina. `data: null` mientras este en borrador.
    Route::get('/oesi', [OesiApiController::class, 'mostrar']);
});

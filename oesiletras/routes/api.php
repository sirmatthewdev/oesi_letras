<?php

use App\Http\Controllers\Api\OesiApiController;
use App\Http\Controllers\Api\SitioApiController;
use App\Http\Controllers\Api\VisitaController;
use Illuminate\Support\Facades\Route;

/*
 * API de contenido, de solo lectura salvo el aviso de visita.
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

    // La unica escritura: el aviso de visita de cada pagina publica. El limite
    // por IP es lo que impide llenar la tabla de conteos inventados.
    Route::post('/visita', [VisitaController::class, 'registrar'])->middleware('throttle:60,1');
});

<?php

/**
 * CORS para la API que consume el sitio público.
 *
 * Hace falta desde que el sitio dejó de servirse desde este mismo dominio: el
 * formulario de solicitud vive en Astro y envía aquí, y sin esto el navegador
 * bloquea la petición antes de que salga.
 *
 * Los orígenes se declaran, no se abren con `*`: este es el único endpoint que
 * escribe, y dejarlo abierto a cualquier origen invita a que lo llamen desde
 * cualquier página.
 *
 * Cuando Nginx sirva el sitio estático y la API bajo el mismo dominio —el
 * destino de esta migración— las peticiones dejarán de ser cross-origin y esto
 * pasará a ser solo la red de seguridad del entorno de desarrollo.
 */
return [
    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    'allowed_origins' => array_filter(explode(',', (string) env(
        'OESI_ORIGENES_PERMITIDOS',
        'http://localhost:4321,http://localhost:4322'
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Accept', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 3600,

    // Sin credenciales: la API pública no usa sesión ni cookies.
    'supports_credentials' => false,
];

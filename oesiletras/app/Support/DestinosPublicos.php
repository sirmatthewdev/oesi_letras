<?php

namespace App\Support;

/**
 * Los destinos internos que el menú puede elegir por nombre.
 *
 * El sitio es un inicio y una página por tema; el curso y las habilidades son
 * secciones de una de ellas, con su ancla. Se eligen por nombre y no
 * escribiendo la URL para que un cambio de ruta se corrija aquí y no en cada
 * entrada del menú: cuando las secciones pasaron de anclas del inicio a
 * páginas, el menú guardado se actualizó sin tocarlo.
 */
class DestinosPublicos
{
    /** @return array<string, string> nombre → ruta del sitio */
    public static function mapa(): array
    {
        return [
            'home' => '/',
            'curso' => '/#curso-preparacion',
            'examen' => '/examen',
            'habilidades' => '/examen#habilidades',
            'modalidades' => '/modalidades',
            'cronograma' => '/cronograma',
            'inscripcion' => '/inscripcion',
            'preguntas' => '/preguntas',
        ];
    }

    public static function ruta(?string $nombre): ?string
    {
        if (! $nombre) {
            return null;
        }

        return self::mapa()[$nombre] ?? null;
    }
}

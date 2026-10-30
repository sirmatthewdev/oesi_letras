<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Conteo diario de visitas al sitio público, por dimensión.
 *
 * Cada visita suma 1 en varias filas del día: la de su página, la de su tipo
 * de dispositivo, la de su procedencia y, si es la primera página de la
 * pestaña, la de `sesion`. Ver la migración para el porqué de contar en vez de
 * guardar visitas sueltas.
 */
class Visita extends Model
{
    public const PAGINA = 'pagina';
    public const SESION = 'sesion';
    public const DISPOSITIVO = 'dispositivo';
    public const REFERENTE = 'referente';

    public $timestamps = false;

    protected $fillable = ['fecha', 'dimension', 'valor', 'total'];

    protected $casts = [
        'fecha' => 'date',
        'total' => 'integer',
    ];

    /**
     * Suma una visita. `$referente` es solo el dominio de procedencia, o null
     * si se llegó escribiendo la dirección o desde el propio sitio.
     */
    public static function registrar(string $ruta, string $dispositivo, ?string $referente, bool $nuevaSesion): void
    {
        $hoy = now()->toDateString();

        $filas = [
            [self::PAGINA, $ruta],
            [self::DISPOSITIVO, $dispositivo],
        ];

        // Procedencia y sesión solo cuentan al entrar: si no, cada clic dentro
        // del sitio sumaría una «visita directa» más.
        if ($nuevaSesion) {
            $filas[] = [self::SESION, 'total'];
            $filas[] = [self::REFERENTE, $referente ?? 'directo'];
        }

        // Un upsert por fila y no leer-sumar-guardar: dos visitas a la vez se
        // pisarían el conteo.
        DB::table('visitas')->upsert(
            array_map(fn ($f) => ['fecha' => $hoy, 'dimension' => $f[0], 'valor' => $f[1], 'total' => 1], $filas),
            ['fecha', 'dimension', 'valor'],
            ['total' => DB::raw('total + 1')]
        );
    }

    public function scopeDimension(Builder $query, string $dimension): Builder
    {
        return $query->where('dimension', $dimension);
    }

    public function scopeDesde(Builder $query, Carbon $desde): Builder
    {
        return $query->where('fecha', '>=', $desde->toDateString());
    }

    /** Total de una dimensión entre dos fechas, ambas incluidas. */
    public static function totalEntre(string $dimension, Carbon $desde, Carbon $hasta): int
    {
        return (int) static::dimension($dimension)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->sum('total');
    }

    /**
     * Serie diaria de una dimensión, con los días sin visitas en 0: sin
     * rellenarlos, la gráfica uniría el martes con el viernes como si el
     * miércoles y el jueves no hubieran existido.
     *
     * @return Collection<string, int>  fecha (Y-m-d) => total
     */
    public static function porDia(string $dimension, int $dias): Collection
    {
        $desde = now()->subDays($dias - 1)->startOfDay();

        $conteos = static::dimension($dimension)
            ->desde($desde)
            ->selectRaw('fecha, SUM(total) as suma')
            ->groupBy('fecha')
            ->pluck('suma', 'fecha')
            ->mapWithKeys(fn ($suma, $fecha) => [Carbon::parse($fecha)->toDateString() => (int) $suma]);

        return collect(range(0, $dias - 1))
            ->mapWithKeys(function ($i) use ($desde, $conteos) {
                $dia = $desde->copy()->addDays($i)->toDateString();

                return [$dia => $conteos[$dia] ?? 0];
            });
    }

    /**
     * Los valores más frecuentes de una dimensión en los últimos `$dias`.
     *
     * @return Collection<string, int>  valor => total, de mayor a menor
     */
    public static function ranking(string $dimension, int $dias, int $limite = 8): Collection
    {
        return static::dimension($dimension)
            ->desde(now()->subDays($dias - 1)->startOfDay())
            ->selectRaw('valor, SUM(total) as suma')
            ->groupBy('valor')
            ->orderByDesc('suma')
            ->limit($limite)
            ->pluck('suma', 'valor')
            ->map(fn ($suma) => (int) $suma);
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Visita;
use Filament\Widgets\ChartWidget;

class PaginasMasVistas extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Páginas más vistas';

    protected ?string $description = 'Últimos 30 días';

    protected ?string $pollingInterval = null;

    /** Nombres legibles para las rutas del sitio; las que no estén salen tal cual. */
    private const NOMBRES = [
        '/' => 'Portada',
        '/examen' => 'El examen',
        '/modalidades' => 'Modalidades',
        '/cronograma' => 'Cronograma',
        '/inscripcion' => 'Inscripción',
        '/preguntas' => 'Preguntas frecuentes',
        '/404' => 'Página no encontrada',
    ];

    protected function getData(): array
    {
        $ranking = Visita::ranking(Visita::PAGINA, 30);

        return [
            'datasets' => [[
                'label' => 'Páginas vistas',
                'data' => $ranking->values()->all(),
                'backgroundColor' => '#143B63',
                'borderRadius' => 4,
            ]],
            'labels' => $ranking->keys()->map(fn ($ruta) => self::NOMBRES[$ruta] ?? $ruta)->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Visita;
use Filament\Widgets\ChartWidget;

class Dispositivos extends ChartWidget
{
    protected static ?int $sort = 5;

    protected ?string $heading = 'Dispositivos';

    protected ?string $description = 'Últimos 30 días';

    protected ?string $maxHeight = '240px';

    protected ?string $pollingInterval = null;

    private const NOMBRES = [
        'movil' => 'Móvil',
        'tablet' => 'Tablet',
        'escritorio' => 'Computadora',
        'desconocido' => 'Desconocido',
    ];

    protected function getData(): array
    {
        $ranking = Visita::ranking(Visita::DISPOSITIVO, 30);

        return [
            'datasets' => [[
                'data' => $ranking->values()->all(),
                'backgroundColor' => ['#143B63', '#B6A350', '#5B7FA6', '#CBD5E1'],
            ]],
            'labels' => $ranking->keys()->map(fn ($v) => self::NOMBRES[$v] ?? $v)->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

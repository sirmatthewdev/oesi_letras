<?php

namespace App\Filament\Widgets;

use App\Models\Visita;
use Filament\Widgets\ChartWidget;

/** De dónde llega la gente: buscadores, redes, WhatsApp o la dirección escrita. */
class Procedencia extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Procedencia';

    protected ?string $description = 'Últimos 30 días';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $ranking = Visita::ranking(Visita::REFERENTE, 30);

        return [
            'datasets' => [[
                'label' => 'Visitas',
                'data' => $ranking->values()->all(),
                'backgroundColor' => '#B6A350',
                'borderRadius' => 4,
                'maxBarThickness' => 28,
            ]],
            // WhatsApp no manda procedencia, así que sus enlaces caen en «Directo».
            'labels' => $ranking->keys()->map(fn ($v) => $v === 'directo' ? 'Directo o WhatsApp' : $v)->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

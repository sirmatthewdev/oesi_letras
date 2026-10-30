<?php

namespace App\Filament\Widgets;

use App\Models\Visita;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class VisitasPorDia extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Visitas por día';

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => 'Últimos 7 días',
            '30' => 'Últimos 30 días',
            '90' => 'Últimos 90 días',
        ];
    }

    protected function getData(): array
    {
        $dias = (int) ($this->filter ?: 30);
        $paginas = Visita::porDia(Visita::PAGINA, $dias);
        $sesiones = Visita::porDia(Visita::SESION, $dias);

        return [
            'datasets' => [
                [
                    'label' => 'Páginas vistas',
                    'data' => $paginas->values()->all(),
                    'borderColor' => '#143B63',
                    'backgroundColor' => 'rgba(20, 59, 99, 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Visitas',
                    'data' => $sesiones->values()->all(),
                    'borderColor' => '#B6A350',
                    'backgroundColor' => 'transparent',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $paginas->keys()->map(fn ($d) => Carbon::parse($d)->translatedFormat('j M'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

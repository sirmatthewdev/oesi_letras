<?php

namespace App\Filament\Widgets;

use App\Models\Visita;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Las cifras de cabecera: hoy, la semana y el mes, cada una contra la anterior. */
class ResumenVisitas extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    // Sin sondeo: con una sola vCPU, recalcular cada 5 s para nadie no sale gratis.
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $hoy = now();

        $visitasHoy = Visita::totalEntre(Visita::PAGINA, $hoy, $hoy);
        $visitasAyer = Visita::totalEntre(Visita::PAGINA, $hoy->copy()->subDay(), $hoy->copy()->subDay());

        $semana = Visita::totalEntre(Visita::PAGINA, $hoy->copy()->subDays(6), $hoy);
        $semanaAnterior = Visita::totalEntre(Visita::PAGINA, $hoy->copy()->subDays(13), $hoy->copy()->subDays(7));

        $sesiones = Visita::totalEntre(Visita::SESION, $hoy->copy()->subDays(29), $hoy);
        $sesionesAnteriores = Visita::totalEntre(Visita::SESION, $hoy->copy()->subDays(59), $hoy->copy()->subDays(30));

        return [
            $this->comparada('Páginas vistas hoy', $visitasHoy, $visitasAyer, 'ayer')
                ->icon(Heroicon::Eye),
            $this->comparada('Páginas vistas · 7 días', $semana, $semanaAnterior, 'los 7 días anteriores')
                ->chart(Visita::porDia(Visita::PAGINA, 14)->values()->all())
                ->icon(Heroicon::ChartBar),
            $this->comparada('Visitas · 30 días', $sesiones, $sesionesAnteriores, 'los 30 días anteriores')
                ->chart(Visita::porDia(Visita::SESION, 30)->values()->all())
                ->icon(Heroicon::Users),
        ];
    }

    private function comparada(string $etiqueta, int $actual, int $anterior, string $contra): Stat
    {
        $stat = Stat::make($etiqueta, number_format($actual, 0, ',', '.'));

        if ($anterior === 0) {
            return $stat->description($actual > 0 ? "Sin datos de {$contra}" : 'Aún sin visitas');
        }

        $cambio = (int) round(($actual - $anterior) / $anterior * 100);

        return $stat
            ->description(($cambio >= 0 ? '+' : '') . "{$cambio} % frente a {$contra}")
            ->descriptionIcon($cambio >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
            ->color($cambio >= 0 ? 'success' : 'danger');
    }
}

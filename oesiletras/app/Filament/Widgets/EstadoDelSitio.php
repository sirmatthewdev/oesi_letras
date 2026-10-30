<?php

namespace App\Filament\Widgets;

use App\Models\OesiSetting;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Si lo guardado en el panel llegó al sitio. Es la pregunta que hace quien
 * edita y no tenía dónde mirar: la publicación va por la cola y, si el
 * servicio de build está caído, el panel guarda sin quejarse y el sitio sigue
 * enseñando lo anterior.
 */
class EstadoDelSitio extends StatsOverviewWidget
{
    protected static ?int $sort = 6;

    protected ?string $heading = 'Estado del sitio';

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        return [
            $this->ultimaPublicacion(),
            $this->ultimoCambio(),
            $this->trabajosFallidos(),
        ];
    }

    private function ultimaPublicacion(): Stat
    {
        // La deja ReconstruirSitio cada vez que pide un build.
        $ultima = Cache::get('sitio.ultima_reconstruccion');

        if (! $ultima) {
            return Stat::make('Última publicación', 'Sin registro')
                ->description('No se ha publicado desde el panel en los últimos 30 días')
                ->icon(Heroicon::ArrowPath);
        }

        $stat = Stat::make('Última publicación', Carbon::parse($ultima['pedida_en'])->diffForHumans())
            ->icon(Heroicon::ArrowPath);

        return $ultima['aceptada']
            ? $stat->description('El servicio de build la aceptó')->color('success')
                ->descriptionIcon(Heroicon::CheckCircle)
            : $stat->description("El servicio de build respondió {$ultima['codigo']}")->color('danger')
                ->descriptionIcon(Heroicon::ExclamationTriangle);
    }

    private function ultimoCambio(): Stat
    {
        $cambio = OesiSetting::actual()?->updated_at;

        return Stat::make('Último cambio de contenido', $cambio?->diffForHumans() ?? '—')
            ->description($cambio?->translatedFormat('j \d\e F Y, H:i') ?? 'Sin contenido')
            ->icon(Heroicon::PencilSquare);
    }

    private function trabajosFallidos(): Stat
    {
        $fallidos = DB::table('failed_jobs')->count();

        return Stat::make('Trabajos fallidos', $fallidos)
            ->description($fallidos ? 'Revisar: alguna publicación pudo no salir' : 'La cola está al día')
            ->color($fallidos ? 'danger' : 'success')
            ->icon($fallidos ? Heroicon::ExclamationTriangle : Heroicon::CheckCircle);
    }
}

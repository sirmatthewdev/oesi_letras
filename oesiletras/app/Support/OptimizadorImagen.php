<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Redimensiona y convierte a WebP lo que se sube desde el panel.
 *
 * Hasta ahora un archivo subido llegaba al sitio tal cual: un cartel de 2560 px
 * y 963 KB se servía en la portada sin tocar, con `fetchpriority="high"`. El
 * personal no tiene por qué saber de compresión, así que se hace aquí.
 *
 * Se usa GD, que viene con PHP y no añade dependencias. Su compresión WebP no
 * es la mejor, pero la ganancia real está en el redimensionado: bajar de
 * 2560 px a 1600 px recorta más que cualquier ajuste de calidad.
 */
class OptimizadorImagen
{
    /** Ancho máximo. Por encima, ninguna pantalla del sitio aprovecha el detalle. */
    public const ANCHO_MAXIMO = 1600;

    public const CALIDAD = 82;

    /** Memoria que se deja libre para el resto de la petición. */
    private const RESERVA_APLICACION = 64 * 1024 * 1024;

    /**
     * Guarda la imagen optimizada y devuelve su ruta relativa en el disco.
     *
     * Si algo falla —formato raro, GD sin soporte, resultado más pesado que el
     * original— se guarda el archivo tal cual: más vale una imagen grande que
     * un error al publicar.
     */
    public static function guardar(
        UploadedFile $archivo,
        string $carpeta,
        string $disco = 'public',
        ?int $anchoMaximo = null
    ): string {
        $anchoMaximo ??= self::ANCHO_MAXIMO;

        try {
            $optimizada = self::procesar($archivo, $anchoMaximo);

            if ($optimizada !== null) {
                $ruta = $carpeta . '/' . Str::random(40) . '.webp';
                Storage::disk($disco)->put($ruta, $optimizada);

                return $ruta;
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo optimizar la imagen subida: ' . $e->getMessage());
        }

        return $archivo->store($carpeta, $disco);
    }

    /** Medidas finales de una imagen ya guardada, para el `width`/`height` del HTML. */
    public static function medidas(string $ruta, string $disco = 'public'): array
    {
        $completa = Storage::disk($disco)->path($ruta);
        $medidas = @getimagesize($completa);

        return [$medidas[0] ?? null, $medidas[1] ?? null];
    }

    /**
     * ¿Hay memoria para descomprimir esta imagen y su copia redimensionada?
     *
     * GD reserva 4 bytes por píxel (RGBA) más overhead propio. Se cuentan las
     * dos imágenes vivas a la vez —origen y destino— y se deja un margen para
     * el resto de la petición.
     */
    public static function cabeEnMemoria(int $ancho, int $alto): bool
    {
        $limite = self::limiteMemoriaBytes();

        if ($limite <= 0) {
            return true;   // Sin límite configurado.
        }

        $destinoAncho = min($ancho, self::ANCHO_MAXIMO);
        $destinoAlto = (int) round($destinoAncho * $alto / max($ancho, 1));

        // 4 bytes por píxel (RGBA) y un 25 % de holgura para las estructuras
        // internas de GD. Un factor mayor rechazaba carteles de 3000 px, que
        // son de tamaño perfectamente normal y sí conviene optimizar.
        $necesario = (($ancho * $alto) + ($destinoAncho * $destinoAlto)) * 4 * 1.25;

        // Se descuenta lo que ya consume el proceso o una reserva mínima, lo
        // que sea mayor. Mirar solo la reserva no bastaba: en un proceso que ya
        // llevaba memoria ocupada seguía intentándolo y reventaba igual.
        $ocupado = max(memory_get_usage(true), self::RESERVA_APLICACION);

        return $necesario < ($limite - $ocupado);
    }

    private static function limiteMemoriaBytes(): int
    {
        $valor = trim((string) ini_get('memory_limit'));

        if ($valor === '' || $valor === '-1') {
            return 0;
        }

        $unidad = strtolower(substr($valor, -1));
        $numero = (int) $valor;

        return match ($unidad) {
            'g' => $numero * 1024 ** 3,
            'm' => $numero * 1024 ** 2,
            'k' => $numero * 1024,
            default => $numero,
        };
    }

    /** Devuelve el WebP optimizado, o null si no compensa tocarlo. */
    private static function procesar(UploadedFile $archivo, int $anchoMaximo): ?string
    {
        if (! function_exists('imagewebp')) {
            return null;
        }

        $medidas = @getimagesize($archivo->getRealPath());
        if (! $medidas) {
            return null;
        }

        [$ancho, $alto, $tipo] = $medidas;

        // GD descomprime la imagen entera en memoria. Sin esta comprobación,
        // un cartel de 6000×8000 agota el límite y mata la petición con un
        // error fatal que ni siquiera se puede capturar: el administrador ve
        // una página en blanco al guardar. Mejor no optimizarla y guardarla
        // tal cual.
        if (! self::cabeEnMemoria($ancho, $alto)) {
            Log::info("Imagen demasiado grande para optimizar en memoria ({$ancho}x{$alto}); se guarda sin tocar.");

            return null;
        }

        $origen = match ($tipo) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($archivo->getRealPath()),
            IMAGETYPE_PNG => @imagecreatefrompng($archivo->getRealPath()),
            IMAGETYPE_WEBP => @imagecreatefromwebp($archivo->getRealPath()),
            IMAGETYPE_GIF => @imagecreatefromgif($archivo->getRealPath()),
            default => null,
        };

        if (! $origen) {
            return null;
        }

        $nuevoAncho = min($ancho, $anchoMaximo);
        $nuevoAlto = (int) round($nuevoAncho * $alto / $ancho);

        $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

        // Sin esto, un PNG con transparencia sale con el fondo negro.
        imagealphablending($destino, false);
        imagesavealpha($destino, true);
        imagefill($destino, 0, 0, imagecolorallocatealpha($destino, 0, 0, 0, 127));

        imagecopyresampled($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

        ob_start();
        imagewebp($destino, null, self::CALIDAD);
        $webp = ob_get_clean();

        imagedestroy($origen);
        imagedestroy($destino);

        // Si no se gana nada, se deja el archivo original.
        return ($webp && strlen($webp) < $archivo->getSize()) ? $webp : null;
    }
}

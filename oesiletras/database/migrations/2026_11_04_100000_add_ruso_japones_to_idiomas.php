<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ruso y Japonés, que la oficina también evalúa, al final de los idiomas.
 *
 * Solo los que falten: se compara por el nombre sin tildes ni mayúsculas, y lo
 * que la oficina ya haya escrito no se toca. Su público y modalidad quedan por
 * completar en el panel (pestaña Idiomas).
 */
return new class extends Migration
{
    private const NUEVOS = [
        ['nombre' => 'Ruso', 'saludo' => 'Привет', 'publico' => null, 'modalidades' => [], 'imagen' => null, 'imagen_alt' => null],
        ['nombre' => 'Japonés', 'saludo' => 'こんにちは', 'publico' => null, 'modalidades' => [], 'imagen' => null, 'imagen_alt' => null],
    ];

    public function up(): void
    {
        $fila = DB::table('oesi_settings')->whereNotNull('nombre')->first();
        if (! $fila) {
            return;
        }

        $idiomas = array_values(json_decode($fila->idiomas ?? '[]', true) ?: []);
        $hay = array_map(fn ($i) => Str::lower(Str::ascii($i['nombre'] ?? '')), $idiomas);

        foreach (self::NUEVOS as $nuevo) {
            if (! in_array(Str::lower(Str::ascii($nuevo['nombre'])), $hay, true)) {
                $idiomas[] = $nuevo;
            }
        }

        DB::table('oesi_settings')->where('id', $fila->id)->update(['idiomas' => json_encode($idiomas, JSON_UNESCAPED_UNICODE)]);
    }

    public function down(): void
    {
        $fila = DB::table('oesi_settings')->whereNotNull('nombre')->first();
        if (! $fila) {
            return;
        }

        $idiomas = array_values(array_filter(
            json_decode($fila->idiomas ?? '[]', true) ?: [],
            fn ($i) => ! in_array($i['nombre'] ?? '', ['Ruso', 'Japonés'], true)
        ));
        DB::table('oesi_settings')->where('id', $fila->id)->update(['idiomas' => json_encode($idiomas, JSON_UNESCAPED_UNICODE)]);
    }
};

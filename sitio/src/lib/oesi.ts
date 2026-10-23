/**
 * Reglas de presentación de /oesi que no dependen del DOM.
 *
 * Se separan de la página para poder probarlas: el formato de un precio o lo
 * que se pinta en negrita no deberían descubrirse mirando la pantalla.
 */

/**
 * Importe en soles, siempre con dos decimales: «S/ 300.00».
 *
 * Una sola función para las dos tablas —costos y tarifario—: en la landing
 * anterior cada una formateaba por su cuenta y el mismo examen salía «S/. 300.00»
 * en una y podía salir distinto en otra. «S/» sin punto es el símbolo vigente.
 */
export function soles(monto: number | null): string {
    if (monto === null || !Number.isFinite(monto)) return '—';

    return (
        'S/ ' +
        new Intl.NumberFormat('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(monto)
    );
}

export type Trozo = { texto: string; negrita: boolean };

/**
 * Parte un texto en trozos normales y en negrita, según los `**` que escribe
 * el panel.
 *
 * No se acepta HTML: el texto viene de un formulario y se pinta escapado. Esto
 * es lo único que se interpreta, y sale como trozos —no como cadena con
 * etiquetas— para que la plantilla los pinte con `<strong>` sin pasar nunca
 * por `set:html`.
 *
 * Un `**` sin pareja se deja tal cual: es más fácil de ver y corregir en la
 * página que una negrita que se come el resto del párrafo.
 */
export function trozos(texto: string | null | undefined): Trozo[] {
    if (!texto) return [];

    const partes = texto.split('**');

    // Número par de partes = número impar de marcas: hay una sin cerrar.
    if (partes.length % 2 === 0) return [{ texto, negrita: false }];

    return partes
        .map((parte, i) => ({ texto: parte, negrita: i % 2 === 1 }))
        .filter((t) => t.texto !== '');
}

/** El mismo texto sin marcas, para atributos y lectores que no pintan negrita. */
export function sinMarcas(texto: string | null | undefined): string {
    return trozos(texto)
        .map((t) => t.texto)
        .join('');
}

export type GrupoPreguntas<T> = { clave: string; etiqueta: string; items: T[] };

/**
 * Reparte las preguntas frecuentes por tema, en el orden de los temas.
 *
 * Dentro de cada tema se conserva el orden en que las cargó la oficina. Las que
 * no tienen tema —o tienen uno que ya no existe— van al final, en «Otras
 * preguntas», en vez de desaparecer. Un tema sin preguntas no se pinta.
 */
export function agruparPreguntas<T extends { categoria: string | null }>(
    temas: { clave: string; etiqueta: string }[],
    items: T[]
): GrupoPreguntas<T>[] {
    const conocidas = new Set(temas.map((t) => t.clave));

    const grupos = temas.map((t) => ({
        clave: t.clave,
        etiqueta: t.etiqueta,
        items: items.filter((i) => i.categoria === t.clave),
    }));

    const otras = items.filter((i) => !i.categoria || !conocidas.has(i.categoria));
    if (otras.length) grupos.push({ clave: 'otras', etiqueta: 'Otras preguntas', items: otras });

    return grupos.filter((g) => g.items.length > 0);
}

/**
 * Texto preparado para buscar: minúsculas, sin tildes ni diéresis y con los
 * espacios colapsados. «Devolución» y «devolucion» tienen que encontrarse.
 */
export function normalizar(texto: string): string {
    return texto
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();
}

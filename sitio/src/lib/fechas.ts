/**
 * Las fechas del cronograma: formato y vigencia.
 *
 * Las fechas llegan de la API en ISO con la hora de Lima («2026-09-21T18:00:00
 * -05:00»). Todo se formatea en esa zona, sea cual sea la del navegador o la
 * del servidor que construye el sitio: un examen a las 6 de la tarde en Lima
 * no puede salir «a las 11 p. m.» porque el build corrió en UTC.
 */

export type FechaExamen = {
    idioma: string;
    inicio: string;
    cierre_inscripcion: string;
    modalidad: 'presencial' | 'virtual';
    publico: string | null;
};

const ZONA = 'America/Lima';

// Los nombres, propios: las abreviaturas de Intl cambian según la versión de
// ICU y el país («Set» para septiembre en es-PE, «sept.» en otras). Del
// navegador o de Node solo se toman los números, ya en la hora de Lima.
const SEMANA = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
const SEMANA_CORTA = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

const enLima = (iso: string) => {
    const p = Object.fromEntries(
        new Intl.DateTimeFormat('en-US', {
            timeZone: ZONA,
            year: 'numeric',
            month: 'numeric',
            day: 'numeric',
            weekday: 'short',
            hour: 'numeric',
            minute: '2-digit',
            hourCycle: 'h23',
        })
            .formatToParts(new Date(iso))
            .map((x) => [x.type, x.value])
    );
    const semana = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].indexOf(p.weekday);
    return { dia: Number(p.day), mes: Number(p.month) - 1, semana, hora: Number(p.hour), minuto: p.minute };
};

/** Las piezas de una fecha, para pintarla: «21», «lunes», «sep», «6:00 p. m.». */
export function piezas(iso: string) {
    const l = enLima(iso);
    const h12 = l.hora % 12 || 12;
    return {
        dia: String(l.dia),
        semana: SEMANA[l.semana],
        semanaCorta: SEMANA_CORTA[l.semana],
        mes: MESES[l.mes],
        mesCorto: MESES[l.mes].slice(0, 3),
        hora: `${h12}:${l.minuto} ${l.hora < 12 ? 'a. m.' : 'p. m.'}`,
    };
}

/** «lun 21 sep · 6:00 p. m.», para avisos de una línea. */
export function corta(iso: string): string {
    const p = piezas(iso);
    return `${p.semanaCorta} ${p.dia} ${p.mesCorto} · ${p.hora}`;
}

/** «lunes 21 de septiembre», para frases. */
export function larga(iso: string): string {
    const p = piezas(iso);
    return `${p.semana} ${p.dia} de ${p.mes}`;
}

/**
 * Las que aún no han empezado, en orden. Se usa al construir el sitio; en el
 * navegador, `data-vence` oculta las que pasan después del build (ver el
 * script de `layouts/Base.astro`).
 */
export function vigentes(fechas: FechaExamen[], ahora: Date = new Date()): FechaExamen[] {
    return fechas.filter((f) => new Date(f.inicio) > ahora).sort((a, b) => a.inicio.localeCompare(b.inicio));
}

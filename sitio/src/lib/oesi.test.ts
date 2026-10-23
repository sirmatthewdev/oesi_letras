import { describe, expect, it } from 'vitest';
import { agruparPreguntas, normalizar, sinMarcas, soles, trozos } from './oesi';

describe('soles', () => {
    it('da dos decimales y el símbolo vigente', () => {
        expect(soles(300)).toBe('S/ 300.00');
        expect(soles(50)).toBe('S/ 50.00');
        expect(soles(100.5)).toBe('S/ 100.50');
    });

    it('agrupa los miles como en Perú', () => {
        expect(soles(2500)).toBe('S/ 2,500.00');
    });

    it('no inventa un cero cuando no hay importe', () => {
        expect(soles(null)).toBe('—');
        expect(soles(Number.NaN)).toBe('—');
    });
});

describe('trozos', () => {
    it('marca en negrita lo que va entre dobles asteriscos', () => {
        expect(trozos('Ingresar con **veinte minutos** de anticipación')).toEqual([
            { texto: 'Ingresar con ', negrita: false },
            { texto: 'veinte minutos', negrita: true },
            { texto: ' de anticipación', negrita: false },
        ]);
    });

    it('admite la negrita al principio y varias en el mismo texto', () => {
        expect(trozos('**No se permite** el uso de **audífonos**')).toEqual([
            { texto: 'No se permite', negrita: true },
            { texto: ' el uso de ', negrita: false },
            { texto: 'audífonos', negrita: true },
        ]);
    });

    it('deja tal cual una marca sin cerrar', () => {
        expect(trozos('Texto con ** suelto')).toEqual([{ texto: 'Texto con ** suelto', negrita: false }]);
    });

    it('no interpreta HTML: lo devuelve como texto', () => {
        expect(trozos('<script>x</script>')).toEqual([{ texto: '<script>x</script>', negrita: false }]);
    });

    it('un texto vacío no produce trozos', () => {
        expect(trozos('')).toEqual([]);
        expect(trozos(null)).toEqual([]);
    });
});

describe('sinMarcas', () => {
    it('quita los asteriscos y conserva el texto', () => {
        expect(sinMarcas('Código de pago en **San Market**')).toBe('Código de pago en San Market');
    });
});

describe('agruparPreguntas', () => {
    const temas = [
        { clave: 'fechas', etiqueta: 'Fechas' },
        { clave: 'pagos', etiqueta: 'Pagos' },
        { clave: 'vacio', etiqueta: 'Sin nada' },
    ];

    it('reparte por tema, en el orden de los temas y conservando el de las preguntas', () => {
        const grupos = agruparPreguntas(temas, [
            { p: 'b', categoria: 'pagos' },
            { p: 'a', categoria: 'fechas' },
            { p: 'c', categoria: 'pagos' },
        ]);

        expect(grupos.map((g) => g.clave)).toEqual(['fechas', 'pagos']);
        expect(grupos[1].items.map((i) => i.p)).toEqual(['b', 'c']);
    });

    it('no pinta un tema sin preguntas', () => {
        expect(agruparPreguntas(temas, [{ categoria: 'fechas' }]).map((g) => g.clave)).toEqual(['fechas']);
    });

    it('lleva a «Otras preguntas» las que no tienen tema o tienen uno que ya no existe', () => {
        const grupos = agruparPreguntas(temas, [{ categoria: null }, { categoria: 'retirado' }, { categoria: 'fechas' }]);

        expect(grupos.at(-1)).toMatchObject({ clave: 'otras', etiqueta: 'Otras preguntas' });
        expect(grupos.at(-1)!.items).toHaveLength(2);
    });

    it('ninguna pregunta se pierde', () => {
        const items = [{ categoria: 'fechas' }, { categoria: null }, { categoria: 'pagos' }, { categoria: 'x' }];
        const total = agruparPreguntas(temas, items).reduce((n, g) => n + g.items.length, 0);

        expect(total).toBe(items.length);
    });
});

describe('normalizar', () => {
    it('quita tildes y mayúsculas para que la búsqueda no dependa de ellas', () => {
        expect(normalizar('Devolución')).toBe(normalizar('devolucion'));
        expect(normalizar('  ¿Cuánto   TIEMPO? ')).toBe('¿cuanto tiempo?');
    });
});

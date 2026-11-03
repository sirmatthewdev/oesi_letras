import { describe, expect, it } from 'vitest';
import { corta, larga, piezas, vigentes, type FechaExamen } from './fechas';

const fecha = (idioma: string, inicio: string): FechaExamen => ({
    idioma,
    inicio,
    cierre_inscripcion: inicio,
    modalidad: 'virtual',
    publico: null,
});

describe('fechas del cronograma', () => {
    it('formatea en hora de Lima aunque el entorno esté en otra zona', () => {
        // 18:00 en Lima son las 23:00 UTC: tiene que seguir diciendo 6 p. m.
        const p = piezas('2026-09-21T18:00:00-05:00');
        expect(p.dia).toBe('21');
        expect(p.semana).toBe('lunes');
        expect(p.hora).toBe('6:00 p. m.');
    });

    it('da la forma corta y la larga', () => {
        expect(corta('2026-09-26T11:00:00-05:00')).toBe('sáb 26 sep · 11:00 a. m.');
        expect(larga('2026-09-26T11:00:00-05:00')).toBe('sábado 26 de septiembre');
    });

    it('deja solo las que no han empezado, en orden', () => {
        const ahora = new Date('2026-09-25T17:30:00-05:00');
        const lista = [
            fecha('Quechua', '2026-09-28T19:00:00-05:00'),
            fecha('Italiano', '2026-09-25T17:00:00-05:00'),
            fecha('Francés', '2026-09-25T18:00:00-05:00'),
        ];
        expect(vigentes(lista, ahora).map((f) => f.idioma)).toEqual(['Francés', 'Quechua']);
    });
});

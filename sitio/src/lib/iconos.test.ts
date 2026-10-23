import { describe, expect, it } from 'vitest';
import {
    faArrowUpRightFromSquare,
    faCalendarAlt,
    faCircleInfo,
    faEnvelope,
    faHouse,
    faPhone,
    faUserPlus,
} from '@fortawesome/free-solid-svg-icons';
import { iconoDeAccion } from './iconos';

/**
 * El icono de un botón nombra su acción. Se prueba aquí y no en el navegador
 * porque varios de estos destinos los escribe la oficina desde el panel: lo que
 * hay que fijar es la correspondencia entre destino e icono.
 */
describe('iconoDeAccion', () => {
    it('nombra el canal cuando la acción no es una página', () => {
        expect(iconoDeAccion('mailto:examensuficiencia.flch@unmsm.edu.pe')).toBe(faEnvelope);
        expect(iconoDeAccion('tel:+51914033108')).toBe(faPhone);
    });

    it('nombra las secciones que tienen una acción propia', () => {
        expect(iconoDeAccion('/#inscripcion')).toBe(faUserPlus);
        expect(iconoDeAccion('#inscripcion')).toBe(faUserPlus);
        expect(iconoDeAccion('/#cronograma')).toBe(faCalendarAlt);
        expect(iconoDeAccion('/')).toBe(faHouse);
    });

    it('avisa de que un enlace externo sale del sitio', () => {
        expect(iconoDeAccion('https://sanmarket.unmsm.edu.pe/')).toBe(faArrowUpRightFromSquare);
        expect(iconoDeAccion('HTTP://ejemplo.pe')).toBe(faArrowUpRightFromSquare);
    });

    it('cae en «hay más sobre esto» cuando el destino no dice qué es', () => {
        expect(iconoDeAccion('/#preguntas')).toBe(faCircleInfo);
    });

    it('no se despista con los espacios de alrededor', () => {
        expect(iconoDeAccion('  /#inscripcion  ')).toBe(faUserPlus);
    });
});

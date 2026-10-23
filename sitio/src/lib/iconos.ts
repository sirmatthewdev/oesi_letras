import type { IconDefinition } from '@fortawesome/fontawesome-svg-core';
import {
    faArrowUpRightFromSquare,
    faCalendarAlt,
    faCircleInfo,
    faEnvelope,
    faHouse,
    faPhone,
    faUserPlus,
} from '@fortawesome/free-solid-svg-icons';

/**
 * El icono de un botón: lo que hace, no hacia dónde apunta.
 *
 * Una flecha a la derecha en todos los botones no dice nada que el propio
 * botón no diga ya. El icono nombra la acción —escribir, inscribirse, salir a
 * otra web—, y lo decide el destino porque varias de estas URL las escribe la
 * oficina desde el panel: elegido a mano en la plantilla, el icono se quedaría
 * desfasado el día que alguien cambie el enlace.
 */
const POR_DESTINO: [RegExp, IconDefinition][] = [
    // Medios de contacto: el icono nombra el canal que se va a abrir.
    [/^mailto:/i, faEnvelope],
    [/^tel:/i, faPhone],

    // Fuera del sitio: el signo de «esto abre en otra parte».
    [/^https?:\/\//i, faArrowUpRightFromSquare],

    // Las secciones de la página que tienen una acción propia.
    [/^\/?#inscripcion$/i, faUserPlus],
    [/^\/?#cronograma$/i, faCalendarAlt],
    [/^\/$/, faHouse],
];

/** Para lo demás, el círculo de información: «aquí hay más sobre esto». */
const POR_DEFECTO = faCircleInfo;

export function iconoDeAccion(url: string): IconDefinition {
    const destino = url.trim();

    for (const [patron, icono] of POR_DESTINO) {
        if (patron.test(destino)) return icono;
    }

    return POR_DEFECTO;
}

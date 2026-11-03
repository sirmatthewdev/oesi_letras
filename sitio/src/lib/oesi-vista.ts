/**
 * Lo que comparten varias páginas de la OESI: los iconos que el panel deja
 * elegir por nombre y las clases de las tablas. Lo que usa una sola página
 * vive en ella (`components/oesi/paginas/`).
 */
import { faBan, faBookOpen, faBuildingColumns, faCommentDots, faHeadphones, faHourglassStart, faLaptop, faPenFancy } from '@fortawesome/free-solid-svg-icons';

// Los iconos que el panel deja elegir, por nombre. Uno desconocido no rompe
// la tarjeta: se queda sin icono.
export const ICONOS = {
    lectura: faBookOpen,
    escritura: faPenFancy,
    audicion: faHeadphones,
    oral: faCommentDots,
    presencial: faBuildingColumns,
    virtual: faLaptop,
    antes: faHourglassStart,
    durante: faBan,
} as const;
export const icono = (clave: string | null) => (clave ? ICONOS[clave as keyof typeof ICONOS] : undefined);

// Las tablas del CERSEU (las de admisión): cabecera clara con el texto en azul,
// no la barra azul maciza de la landing anterior.
export const cabeceraTabla = 'px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-unmsm-azul';
export const marcoTabla = 'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm';

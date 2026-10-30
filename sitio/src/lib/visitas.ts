/**
 * Aviso de visita para las analíticas del panel.
 *
 * El sitio es estático y Laravel no ve las páginas que se sirven, así que cada
 * una avisa por su cuenta. Sin cookies ni identificadores: se manda la ruta,
 * el ancho de pantalla (para saber si es móvil) y, solo al entrar, de qué
 * dominio se llegó. «Al entrar» se sabe con `sessionStorage`, que vive lo que
 * vive la pestaña y nunca sale del navegador.
 *
 * La ruta es la de la página tal como la generó Astro (`data-ruta` en el
 * body), no la de la barra de direcciones: una dirección mal escrita cuenta
 * como `/404` y no como una ruta inventada más.
 */
const ENDPOINT = '/api/v1/visita';
const CLAVE = 'oesi-visita';

export function registrarVisita(): void {
    const ruta = document.body.dataset.ruta;

    // Sin ruta es la vista previa, que no debe contar.
    if (!ruta || !('sendBeacon' in navigator)) {
        return;
    }

    // Quien pidió no ser seguido, no se cuenta.
    const nav = navigator as Navigator & { globalPrivacyControl?: boolean };
    if (nav.globalPrivacyControl || navigator.doNotTrack === '1') {
        return;
    }

    let nueva = false;
    try {
        nueva = sessionStorage.getItem(CLAVE) === null;
        sessionStorage.setItem(CLAVE, '1');
    } catch {
        // Almacenamiento bloqueado: cuenta la página y no la entrada.
    }

    const datos = {
        ruta,
        ancho: window.innerWidth,
        referente: nueva ? document.referrer : null,
        nueva,
    };

    navigator.sendBeacon(ENDPOINT, new Blob([JSON.stringify(datos)], { type: 'application/json' }));
}

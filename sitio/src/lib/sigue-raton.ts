/**
 * Lo que sigue al ratón (el mismo efecto que la portada del CERSEU).
 *
 * Marca un elemento con `data-sigue-raton` y esto le escribe dos variables CSS
 * —`--raton-h` y `--raton-v`, de −1 a 1— con la posición del puntero dentro de
 * él. Qué hacer con ellas lo decide la hoja de estilos: en el hero desplazan la
 * fotografía, y en la banda de marca mueven una luz. El script no sabe nada del
 * diseño y el diseño no depende del script.
 *
 * Solo con ratón de verdad (`pointer: fine`) y sin movimiento reducido. El
 * seguimiento interpola —un 6 % de lo que falta por cuadro— para que acompañe
 * al puntero en vez de pegarse a él, y el bucle se detiene cuando ya no queda
 * distancia: no hay un `requestAnimationFrame` corriendo para siempre.
 */
export function seguirAlRaton(): void {
    const zonas = [...document.querySelectorAll<HTMLElement>('[data-sigue-raton]')].filter(
        (zona) => zona.dataset.siguiendo !== '1'
    );

    if (zonas.length === 0) return;

    if (
        !window.matchMedia('(pointer: fine)').matches ||
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    ) {
        return;
    }

    const seguidas = zonas.map((zona) => {
        zona.dataset.siguiendo = '1';
        return { zona, destinoH: 0, destinoV: 0, h: 0, v: 0 };
    });

    let enMarcha = false;

    function acercarse() {
        let queda = false;

        for (const s of seguidas) {
            s.h += (s.destinoH - s.h) * 0.06;
            s.v += (s.destinoV - s.v) * 0.06;

            s.zona.style.setProperty('--raton-h', s.h.toFixed(4));
            s.zona.style.setProperty('--raton-v', s.v.toFixed(4));

            if (Math.abs(s.destinoH - s.h) > 0.002 || Math.abs(s.destinoV - s.v) > 0.002) {
                queda = true;
            }
        }

        if (queda) requestAnimationFrame(acercarse);
        else enMarcha = false;
    }

    function arrancar() {
        if (enMarcha) return;
        enMarcha = true;
        requestAnimationFrame(acercarse);
    }

    // En la ventana y no en cada zona: la cabecera y los botones se montan
    // encima, y escuchando solo a la zona el efecto se quedaba clavado.
    window.addEventListener(
        'pointermove',
        (evento) => {
            if (evento.pointerType !== 'mouse') return;

            for (const s of seguidas) {
                const caja = s.zona.getBoundingClientRect();
                if (caja.width === 0 || caja.height === 0) continue;

                s.destinoH = ((evento.clientX - caja.left) / caja.width - 0.5) * 2;
                s.destinoV = ((evento.clientY - caja.top) / caja.height - 0.5) * 2;
            }

            arrancar();
        },
        { passive: true }
    );

    // Al salir de la ventana todo vuelve al centro.
    document.addEventListener('pointerleave', () => {
        for (const s of seguidas) {
            s.destinoH = 0;
            s.destinoV = 0;
        }

        arrancar();
    });
}

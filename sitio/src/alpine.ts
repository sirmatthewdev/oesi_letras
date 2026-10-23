import type { Alpine } from 'alpinejs';

/**
 * Punto de entrada de Alpine.
 *
 * Astro lo llama antes de arrancarlo, y es donde se registran los datos y las
 * directivas propias. De momento solo hace falta uno: el estado compartido de
 * un acordeón, que varias secciones usan para dejar abierto un solo panel.
 */
export default (Alpine: Alpine) => {
    Alpine.data('acordeon', (inicial: number | null = null) => ({
        abierto: inicial,

        alternar(i: number) {
            // Uno a la vez: dos paneles largos abiertos obligan a desplazarse
            // para comparar, que es justo lo que un acordeon viene a evitar.
            this.abierto = this.abierto === i ? null : i;
        },

        esta(i: number) {
            return this.abierto === i;
        },
    }));

    /**
     * Desplegable de la barra de navegación.
     *
     * Antes era un `<details>`: solo abría al pulsar, y al pulsar se quedaba
     * abierto hasta que alguien volvía a pulsarlo — incluso después de irse con
     * el ratón a otra parte.
     *
     * Abre al pasar el ratón, pero solo donde hay ratón de verdad. En una
     * pantalla táctil `mouseenter` se dispara con el primer toque y el mismo
     * gesto que abre dispara también el clic, así que el menú se abría y se
     * cerraba de golpe. `(hover: hover)` distingue los dos casos.
     *
     * Con teclado no depende del ratón para nada: se abre con Enter o con la
     * flecha abajo, se cierra con Escape devolviendo el foco al botón, y se
     * cierra solo al salir del desplegable con el tabulador.
     */
    Alpine.data('desplegable', () => ({
        abierto: false,

        get conRaton() {
            return window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        },

        entra() {
            if (this.conRaton) this.abierto = true;
        },

        sale() {
            if (this.conRaton) this.abierto = false;
        },

        alternar() {
            this.abierto = !this.abierto;
        },

        cerrar(devolverFoco = false) {
            if (!this.abierto) return;
            this.abierto = false;
            if (devolverFoco) this.$refs.boton?.focus();
        },

        /**
         * Cierra al salir del desplegable con el tabulador.
         *
         * Se comprueba en el fotograma siguiente porque durante `focusout` el
         * foco todavía no ha llegado a su destino: `relatedTarget` miente en
         * algunos navegadores y `activeElement` es el `<body>`.
         */
        alSalirElFoco() {
            requestAnimationFrame(() => {
                if (!this.$el.contains(document.activeElement)) this.abierto = false;
            });
        },
    }));
};

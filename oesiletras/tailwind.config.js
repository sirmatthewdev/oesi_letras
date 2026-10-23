import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    // El sitio es light-only. Con 'class' (en vez del 'media' por defecto) las
    // variantes dark: del scaffolding de Breeze quedan inertes salvo que exista
    // un ancestro .dark (que nunca se agrega), evitando render oscuro accidental
    // en auth/perfil (importante para el recorte blanco de los floating labels).
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                serif: ['Merriweather', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                // Escala institucional derivada de #143B63 = hsl(210, 66%, 23%):
                // -dark para gradientes/hover sobre el azul base, -soft como
                // acento legible sobre fondo azul oscuro (contraste 5.5:1 con
                // #143B63, donde un azul oscuro sería invisible).
                'unmsm-azul': '#143B63',
                'unmsm-azul-light': '#1C5287',
                'unmsm-azul-dark': '#0F2B48',
                'unmsm-azul-soft': '#88B8E7',
                'unmsm-dorado': '#B6A350',
                'unmsm-dorado-light': '#C9AA36',
            },
        },
    },

    plugins: [forms],
};

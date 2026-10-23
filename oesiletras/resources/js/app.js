// JavaScript de las pantallas en Blade que quedan: el inicio de sesión, la
// recuperación de contraseña y el perfil. El panel es Filament, que trae el
// suyo, y el sitio público es Astro.
//
// Tipografías auto-alojadas, sin peticiones a Google Fonts.
import '@fontsource/inter/latin-400.css';
import '@fontsource/inter/latin-500.css';
import '@fontsource/inter/latin-600.css';
import '@fontsource/inter/latin-700.css';
import '@fontsource/playfair-display/latin-600.css';
import '@fontsource/playfair-display/latin-700.css';

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

Alpine.plugin(focus); // x-trap: focus-trap accesible para los modales

window.Alpine = Alpine;
Alpine.start();

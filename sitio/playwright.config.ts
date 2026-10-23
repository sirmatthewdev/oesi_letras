import { defineConfig, devices } from '@playwright/test';

/**
 * Pruebas de comportamiento en navegador.
 *
 * Existen por dos fallos concretos de este proyecto que ninguna prueba de PHP
 * podía ver, porque no eran de datos ni de rutas sino de lo que el navegador
 * hace con ellos:
 *
 *   1. Un IntersectionObserver con el umbral mal puesto dejó los 39 cursos
 *      invisibles. La página respondía 200 y el HTML traía las 39 tarjetas.
 *   2. Los enlaces de la cabecera apuntaban al otro sitio: pulsar «Cursos» te
 *      sacaba del sitio que estabas viendo. También respondía 200.
 *
 * Las dos cosas se vieron mirando, no ejecutando pruebas. Estas las fijan.
 */
export default defineConfig({
    testDir: './e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    reporter: process.env.CI ? 'github' : 'list',

    use: {
        baseURL: process.env.OESI_E2E ?? 'http://localhost:4331',
        trace: 'on-first-retry',
    },

    projects: [
        { name: 'escritorio', use: { ...devices['Desktop Chrome'] } },
        // El móvil no es un extra: el fallo del IntersectionObserver solo se
        // manifestaba ahí, porque la grilla a una columna era tan alta que el
        // umbral resultaba inalcanzable.
        { name: 'movil', use: { ...devices['Pixel 7'] } },
    ],
});

import { expect, test } from '@playwright/test';
import { abrirMenu } from './utiles';

/**
 * El otro fallo: los enlaces internos de la cabecera apuntaban a la aplicación
 * de Laravel, así que pulsar «Cursos» sacaba al visitante del sitio que estaba
 * viendo. Respondía 200 y por eso ninguna comprobación de rutas lo veía.
 */
test.describe('La navegación no saca del sitio', () => {
    test('los enlaces internos de la cabecera son relativos', async ({ page }) => {
        await page.goto('/');

        const destinos = await page
            .locator('header nav a')
            .evaluateAll((enlaces) =>
                enlaces.map((a) => ({
                    href: a.getAttribute('href') ?? '',
                    externo: a.getAttribute('target') === '_blank',
                }))
            );

        const internos = destinos.filter((d) => !d.externo);
        expect(internos.length).toBeGreaterThan(0);

        for (const enlace of internos) {
            expect(enlace.href, `«${enlace.href}» debería ser una ruta, no una URL`).toMatch(/^\//);
        }
    });

    test('pulsar Inscripción en la cabecera lleva a su página sin salir del sitio', async ({ page }) => {
        const respuesta = await page.goto('/');
        // En borrador la portada es «próximamente» y no hay página que abrir.
        test.skip((await page.locator('[data-oesi]').count()) === 0, 'La página de la oficina está en borrador.');
        const origen = new URL(page.url()).origin;

        const navegacion = await abrirMenu(page);
        await navegacion.locator('a[href="/inscripcion"]').first().click();

        await expect(page).toHaveURL(/\/inscripcion$/);
        await expect(page.locator('main h1')).toHaveText('Inscripción');
        expect(new URL(page.url()).origin).toBe(origen);
        expect(respuesta?.status()).toBe(200);
    });

    test('ninguna ruta del menú responde 404', async ({ page, request }) => {
        await page.goto('/');

        const rutas = await page
            .locator('header nav a')
            .evaluateAll((enlaces) =>
                enlaces
                    .map((a) => a.getAttribute('href') ?? '')
                    .filter((h) => h.startsWith('/'))
            );

        for (const ruta of [...new Set(rutas)]) {
            const respuesta = await request.get(ruta);
            expect(respuesta.status(), `${ruta} respondió ${respuesta.status()}`).toBeLessThan(400);
        }
    });
});

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

/**
 * El recorrido entre páginas (fase 1 de navegación). Las páginas de consulta
 * terminaban sin salida y el mismo botón «Inscríbete» llevaba a dos sitios.
 */
test.describe('El recorrido no tiene callejones sin salida', () => {
    test('cada página interior termina con la anterior y la siguiente', async ({ page }) => {
        for (const ruta of ['/examen', '/modalidades', '/cronograma', '/inscripcion', '/preguntas']) {
            await page.goto(ruta);
            const continua = page.locator('nav[aria-label="Continúa"] a');
            await expect(continua, `${ruta} debería cerrar con «Continúa»`).toHaveCount(2);
            for (const href of await continua.evaluateAll((a) => a.map((x) => x.getAttribute('href') ?? ''))) {
                expect(href, `«${href}» en ${ruta}`).toMatch(/^\//);
            }
        }
    });

    test('«Inscríbete» lleva siempre al primer paso, elegir la fecha', async ({ page }) => {
        await page.goto('/');
        const destinos = await page
            .locator('a')
            .filter({ hasText: /^\s*Inscríbete\s*$/ })
            .evaluateAll((a) => [...new Set(a.map((x) => new URL(x.getAttribute('href') ?? '', location.href).pathname))]);
        // Los del hero llevan además su idioma (`?idioma=…`): la misma página, filtrada.
        expect(destinos).toEqual(['/cronograma']);
    });

    test('desde el panel de un idioma, el cronograma llega filtrado por ese idioma', async ({ page }) => {
        await page.goto('/');
        const enlace = page.locator('[data-panel]').filter({ hasText: /francés/i }).locator('a', { hasText: 'Inscríbete' });
        await expect(enlace).toHaveAttribute('href', '/cronograma?idioma=frances');

        await page.goto('/cronograma?idioma=frances');
        await expect(page.locator('select[data-filtro-idioma]')).toHaveValue('frances');
        await expect(page.locator('[data-desplegable]').first().locator('[data-desplegable-valor]')).toHaveText('Francés');
        const visibles = await page
            .locator('#cronograma .examen:not([hidden])')
            .evaluateAll((e) => e.map((x) => (x as HTMLElement).dataset.idioma));
        expect(visibles.length).toBeGreaterThan(0);
        expect(new Set(visibles)).toEqual(new Set(['frances']));

        // Idioma y modalidad se combinan, y los dos van en la dirección.
        const [idioma, modalidad] = [page.locator('[data-desplegable]').nth(0), page.locator('[data-desplegable]').nth(1)];
        await modalidad.locator('[data-desplegable-boton]').click();
        await modalidad.locator('[role="option"][data-valor="virtual"]').click();
        await expect(page).toHaveURL(/idioma=frances&modalidad=virtual$/);

        // Con el teclado también: abrir, ir a «Todos los idiomas», elegir.
        await idioma.locator('[data-desplegable-boton]').focus();
        await page.keyboard.press('Enter');
        await page.keyboard.press('Home');
        await page.keyboard.press('Enter');
        await expect(page.locator('select[data-filtro-idioma]')).toHaveValue('');

        // «Todas» vuelve a la lista completa y limpia la dirección.
        await modalidad.locator('[data-desplegable-boton]').click();
        await modalidad.locator('[role="option"][data-valor=""]').click();
        await expect(page).toHaveURL(/\/cronograma$/);
        expect(await page.locator('#cronograma .examen:not([hidden])').count()).toBeGreaterThan(visibles.length);
    });

    test('en la portada el primer título es el de la oficina', async ({ page }) => {
        await page.goto('/');
        // Los paneles del acordeón no son secciones: no pueden abrir el esquema.
        const primero = await page.locator('main :is(h1, h2, h3)').first().evaluate((h) => h.tagName);
        expect(primero).toBe('H1');
    });
});

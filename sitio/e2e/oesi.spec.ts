import { expect, test, type Page } from '@playwright/test';

/**
 * Las páginas de la Oficina de Examen de Suficiencia en Idiomas: el inicio y
 * una por tema.
 *
 * Fija lo que la landing anterior hacía y lo que hacía mal: sus anclas, que
 * ahora llevan a su página, las imágenes con respaldo —el cronograma vive en otra web
 * y cambia cada mes—, la copia del contacto, que intentaba copiar un dato que
 * no existía, y los enlaces de pago, que no pueden confundirse con los
 * formularios.
 *
 * **Se omite sola mientras la página esté en borrador**, que es como entra en
 * una instalación nueva: entonces la portada dice «próximamente» (lo prueba
 * `portada.spec.ts`). Para probarla sin publicarla, contra la vista previa:
 *
 *   OESI_E2E=<url de la vista previa> npx playwright test oesi
 */

// Cada página con su h1, que es también su entrada del menú.
const PAGINAS: Record<string, string> = {
    '/examen': 'El examen',
    '/modalidades': 'Modalidades',
    '/cronograma': 'Cronograma',
    '/inscripcion': 'Inscripción',
    '/preguntas': 'Preguntas frecuentes',
};

async function abrir(page: Page, ruta = '/') {
    await page.goto(ruta);
    // En borrador la portada es «próximamente»: no hay página que probar.
    test.skip(
        (await page.locator('[data-oesi]').count()) === 0,
        'La página de la oficina está en borrador: la portada dice «próximamente».'
    );
}

test.describe('OESI', () => {
    test('el inicio resume: el recorrido de la inscripción y los accesos, no el contenido entero', async ({ page }) => {
        await abrir(page);

        const pasos = page.locator('#como-inscribirte ol > li a');
        await expect(pasos).toHaveCount(3);
        await expect(pasos.nth(0)).toHaveAttribute('href', '/cronograma');
        await expect(pasos.nth(1)).toHaveAttribute('href', '/inscripcion#paso-pago');
        await expect(pasos.nth(2)).toHaveAttribute('href', '/inscripcion#paso-formulario');

        // El detalle vive en su página.
        await expect(page.locator('main details')).toHaveCount(0);
        await expect(page.locator('main a[href^="https://sanmarket.unmsm.edu.pe/"]')).toHaveCount(0);
    });

    test('cada tema tiene su página, con un solo h1 que se llama como en el menú', async ({ page }) => {
        for (const [ruta, titulo] of Object.entries(PAGINAS)) {
            await abrir(page, ruta);
            await expect(page.locator('main h1'), ruta).toHaveCount(1);
            await expect(page.locator('main h1'), ruta).toHaveText(titulo);
            await expect(page).toHaveTitle(new RegExp(`^${titulo} · `));
        }
    });

    test('el menú marca la página en la que se está', async ({ page, isMobile }) => {
        test.skip(Boolean(isMobile), 'En móvil el menú va en un panel aparte.');
        await abrir(page, '/modalidades');

        await expect(page.locator('header nav[aria-label="Principal"] a[aria-current="page"]')).toHaveAttribute('href', '/modalidades');
    });

    test('las anclas de la landing llevan a su página', async ({ page }) => {
        for (const [ancla, destino] of [
            ['#inscripcion', /\/inscripcion$/],
            ['#habilidades', /\/examen#habilidades$/],
            ['#faq-requisitos', /\/preguntas#faq-requisitos$/],
        ] as const) {
            await page.goto(`/${ancla}`);
            await expect(page, ancla).toHaveURL(destino);
        }
        await expect(page.locator('#faq-requisitos')).toBeInViewport();
    });

    test('abrir con un ancla deja la sección a la vista, bajo la cabecera', async ({ page }) => {
        await abrir(page, '/inscripcion#paso-formulario');
        await page.waitForLoadState('load');
        await page.waitForTimeout(400);

        const arriba = await page.locator('#paso-formulario').evaluate((el) => el.getBoundingClientRect().top);
        const cabecera = await page.locator('header.cabecera').evaluate((el) => el.getBoundingClientRect().bottom);

        expect(arriba, 'la sección quedó fuera de la vista').toBeLessThan(400);
        expect(arriba, 'la sección quedó tapada por la cabecera').toBeGreaterThanOrEqual(cabecera - 2);
    });

    test('el requisito sale antes que lo que pasa en el examen', async ({ page }) => {
        await abrir(page, '/examen');

        const orden = await page
            .locator('main h2')
            .evaluateAll((hs) => hs.map((h) => h.textContent?.trim() ?? ''));
        expect(orden.indexOf('Requisito para rendirlo')).toBeGreaterThanOrEqual(0);
        expect(orden.indexOf('Requisito para rendirlo')).toBeLessThan(orden.indexOf('Calificación, resultados y certificado'));
    });

    test('sin JavaScript cada página se ve entera', async ({ browser }) => {
        const contexto = await browser.newContext({ javaScriptEnabled: false });
        const page = await contexto.newPage();
        await abrir(page, '/examen');

        for (const id of ['requisito', 'habilidades', 'despues']) {
            await expect(page.locator(`#${id}`)).toBeVisible();
        }

        await contexto.close();
    });

    test('las preguntas abren y cierran con teclado', async ({ page }) => {
        await abrir(page, '/preguntas');

        const primera = page.locator('#preguntas details').first();
        await primera.locator('summary').focus();
        await page.keyboard.press('Enter');
        await expect(primera).toHaveAttribute('open', '');
        await expect(primera).toContainText('cada mes');

        await page.keyboard.press('Enter');
        await expect(primera).not.toHaveAttribute('open', '');
    });

    test('las preguntas salen agrupadas por tema y ninguna se pierde', async ({ page }) => {
        await abrir(page, '/preguntas');

        const grupos = page.locator('#preguntas [data-faq-grupo]');
        expect(await grupos.count()).toBeGreaterThanOrEqual(2);
        await expect(page.locator('#preguntas [data-faq-grupo] details')).toHaveCount(12);
    });

    test('el buscador filtra sin tildes y avisa cuando no hay nada', async ({ page }) => {
        await abrir(page, '/preguntas');

        const campo = page.getByLabel('Busca tu pregunta');
        const visibles = page.locator('#preguntas details:not([hidden])');

        // «devolucion» sin tilde encuentra «devolución».
        await campo.fill('devolucion');
        await expect(visibles).toHaveCount(1);
        await expect(visibles.first()).toContainText('devolución');
        await expect(page.locator('[data-faq-cuenta]')).toHaveText('1 pregunta coincide');

        await campo.fill('zzzz');
        await expect(visibles).toHaveCount(0);
        await expect(page.locator('[data-faq-vacio]')).toBeVisible();

        // Vaciar el campo devuelve las doce.
        await campo.fill('');
        await expect(visibles).toHaveCount(12);
        await expect(page.locator('[data-faq-vacio]')).toBeHidden();
    });

    test('cada pago lleva a San Market y no se confunde con un formulario', async ({ page }) => {
        await abrir(page, '/inscripcion');

        const pagos = page.locator('#inscripcion a[href^="https://sanmarket.unmsm.edu.pe/"]');
        await expect(pagos).toHaveCount(5);

        for (const enlace of await pagos.all()) {
            await expect(enlace).toHaveAttribute('target', '_blank');
            await expect(enlace).toHaveAttribute('rel', /noopener/);
        }

        await expect(page.locator('#inscripcion')).toContainText('S/ 300.00');
        await expect(page.locator('#inscripcion')).toContainText('S/ 50.00');

        // Los dos de la convocatoria y los tres de otros trámites, aparte de
        // los pagos.
        await expect(page.locator('#inscripcion a[href^="https://forms.gle/"]')).toHaveCount(5);
    });

    test('el cronograma avisa de las fechas y lleva al pago; sus formularios van en el paso 2', async ({ page }) => {
        await abrir(page, '/cronograma');

        await expect(page.locator('#cronograma [role="note"]')).toContainText('Confírmalas');
        await expect(page.locator('#cronograma a[href="/inscripcion#paso-pago"]').first()).toBeVisible();
        await expect(page.locator('nav[aria-label="Etapas de la inscripción"] [aria-current="step"]')).toContainText('Elige tu fecha');

        await abrir(page, '/inscripcion');
        const convocatoria = page.locator('#paso-formulario');
        await expect(convocatoria.locator('a[href="https://forms.gle/1w1ySuaFFfbPb1Ff9"]')).toBeVisible();
        await expect(convocatoria.locator('a[href="https://forms.gle/QZguJrD9xmQd5DqYA"]')).toBeVisible();
        await expect(convocatoria).toContainText('Formulario de la convocatoria del mes');
        await expect(convocatoria).toContainText('Otros trámites');

        // La numeración es la de las imágenes de la oficina: pago 1, formulario 2.
        await expect(page.locator('#paso-pago')).toContainText('Paso 1');
        await expect(convocatoria).toContainText('Paso 2');
    });

    test('si una imagen no carga, se ve su respaldo con salida', async ({ page }) => {
        // Las imágenes viven en la web de Letras: se simula que no responde.
        await page.route('https://letras.unmsm.edu.pe/**', (ruta) => ruta.abort());
        await abrir(page, '/cronograma');

        const figura = page.locator('#cronograma [data-imagen-oesi]');
        const respaldo = figura.locator('[data-imagen-respaldo]');

        await expect(respaldo).toBeVisible();
        await expect(respaldo).toContainText('No pudimos mostrar el cronograma');
        await expect(respaldo.getByRole('link', { name: 'Abrir la imagen' })).toHaveAttribute(
            'href',
            /CRONOGRAMA-OESI/
        );
        await expect(respaldo.getByRole('link', { name: /WhatsApp/ })).toHaveAttribute('href', /wa\.me\/51914033108/);
        await expect(figura.locator('img')).toBeHidden();
    });

    test('el flyer roto también tiene respaldo, aunque esté dentro de un enlace', async ({ page }) => {
        await page.route('https://letras.unmsm.edu.pe/**', (ruta) => ruta.abort());
        await abrir(page);

        const respaldo = page.locator('#curso-preparacion [data-imagen-respaldo]');
        await expect(respaldo).toBeVisible();
        await expect(respaldo.getByRole('link', { name: 'Ver la información del curso' })).toBeVisible();
    });

    test('copiar el correo avisa del resultado y no rompe nada', async ({ page }) => {
        const errores: string[] = [];
        page.on('pageerror', (e) => errores.push(e.message));
        await abrir(page);

        const boton = page.getByRole('button', { name: 'Copiar el correo' });
        await expect(boton).toBeVisible();
        await boton.click();

        // Fuera de HTTPS el navegador no ofrece la API del portapapeles y entra
        // la vía antigua; las dos tienen que decir algo, nunca callar.
        await expect(page.locator('#oesi-aviso-copia')).toContainText(/Copiado|No se pudo copiar/);
        await expect(page.locator('#oesi-aviso-copia')).toContainText('examensuficiencia.flch@unmsm.edu.pe');
        expect(errores).toEqual([]);
    });

    test('el WhatsApp flotante es el de la oficina', async ({ page }) => {
        await abrir(page);

        await expect(page.getByRole('link', { name: /Escribir por WhatsApp/ })).toHaveAttribute(
            'href',
            'https://wa.me/51914033108?text=Hola%2C%20quiero%20consultar%20sobre%20el%20examen%20de%20suficiencia'
        );
    });

    test('las redes son las de la OESI', async ({ page }) => {
        await abrir(page);

        const hero = page.locator('.hero-portada');
        await expect(hero.getByRole('link', { name: /Instagram de la OESI/ })).toHaveAttribute(
            'href',
            'https://www.instagram.com/oesidiomas.unmsm/'
        );
        await expect(hero.getByRole('link', { name: /Canal de WhatsApp de la OESI/ })).toBeVisible();
    });

    test('ninguna página desborda el ancho de la pantalla', async ({ page }) => {
        for (const ruta of ['/', ...Object.keys(PAGINAS)]) {
            await abrir(page, ruta);

            const desborde = await page.evaluate(
                () => document.documentElement.scrollWidth - document.documentElement.clientWidth
            );
            expect(desborde, `${ruta} desborda ${desborde}px`).toBeLessThanOrEqual(0);
        }
    });

    test('carga sin errores de consola', async ({ page }) => {
        const errores: string[] = [];
        page.on('console', (m) => {
            if (m.type() === 'error') errores.push(m.text());
        });
        page.on('pageerror', (e) => errores.push(e.message));

        for (const ruta of ['/', ...Object.keys(PAGINAS)]) {
            await abrir(page, ruta);
            await page.mouse.wheel(0, 20000);
            await page.waitForTimeout(300);
        }

        // Lo que falle al pedir algo de otra web no es un error de la página.
        expect(errores.filter((e) => !/Failed to load resource/.test(e))).toEqual([]);
    });
});

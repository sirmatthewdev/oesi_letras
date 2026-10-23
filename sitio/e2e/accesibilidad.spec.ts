import { expect, test } from '@playwright/test';
import { abrirMenu } from './utiles';

test.describe('Accesibilidad de la cabecera y el pie', () => {
    test('el desplegable abre con teclado, no solo al pasar el ratón', async ({ page }) => {
        await page.goto('/');

        // En pantalla estrecha la navegación vive en un panel que hay que
        // abrir. La prueba reproduce el camino real del visitante, no se lo
        // salta.
        const navegacion = await abrirMenu(page);

        // En movil el desplegable sigue siendo un <details> —pulsar es el gesto
        // correcto con el dedo—; en escritorio es un boton con su lista, para
        // poder abrir tambien al pasar el raton. El teclado tiene que funcionar
        // en los dos.
        const details = navegacion.locator('details').first();

        if (await details.count()) {
            await details.locator('summary').focus();
            await page.keyboard.press('Enter');
            await expect(details).toHaveAttribute('open', '');
            await expect(details.locator('ul a').first()).toBeVisible();
            return;
        }

        const boton = navegacion.getByRole('button').first();
        await boton.focus();
        await page.keyboard.press('Enter');

        await expect(boton).toHaveAttribute('aria-expanded', 'true');
        await expect(
            boton.locator('xpath=following-sibling::ul').getByRole('link').first()
        ).toBeVisible();
    });

    test('el primer tabulador ofrece saltar al contenido', async ({ page }) => {
        await page.goto('/');
        await page.keyboard.press('Tab');

        const enfocado = page.locator(':focus');
        await expect(enfocado).toHaveText(/Saltar al contenido/i);
    });

    test('hay un solo h1 por página', async ({ page }) => {
        // La 404 se pide por una ruta que no existe: Nginx no sirve /404.html por
        // su nombre, solo como respuesta de error.
        for (const ruta of ['/', '/examen', '/modalidades', '/cronograma', '/inscripcion', '/preguntas', '/esta-ruta-no-existe']) {
            await page.goto(ruta);

            // Acotado a `main`: los selectores CSS de Playwright atraviesan los
            // shadow roots abiertos, y la barra de herramientas de Astro en
            // desarrollo aporta cuatro <h1> propios («Audit», «Settings»...).
            // Contar los del documento entero medía la herramienta, no la página.
            await expect(page.locator('main h1'), `en ${ruta}`).toHaveCount(1);
        }
    });
});

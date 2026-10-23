import { expect, test } from '@playwright/test';

/**
 * La portada responde siempre, publicada o no.
 *
 * Mientras la oficina no publique su contenido, la API no lo sirve y la
 * portada dice «próximamente» con el correo de contacto. Lo que no puede pasar
 * es que la raíz del sitio dé 404 o salga en blanco.
 */
test('la portada responde y nunca está vacía', async ({ page }) => {
    const respuesta = await page.goto('/');
    expect(respuesta?.status()).toBe(200);

    await expect(page.locator('main h1')).toHaveCount(1);

    const publicada = (await page.locator('[data-oesi]').count()) > 0;

    if (publicada) {
        await expect(page.locator('#como-inscribirte')).toBeVisible();
    } else {
        await expect(page.locator('main')).toContainText('Próximamente');
        await expect(page.locator('main a[href^="mailto:"]')).toBeVisible();
    }
});

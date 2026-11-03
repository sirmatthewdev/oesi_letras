import { expect, test, type Page } from '@playwright/test';

/**
 * La auditoría de calidad visual, como prueba: contraste AA del texto,
 * imágenes servidas a su resolución y un solo h1 por página.
 *
 * Nació de una auditoría a mano tras el rediseño, que encontró rótulos del pie
 * en 3,96:1, dorados sobre blanco en 2,5:1 y fotos del hero ampliadas 1,3 veces
 * en el móvil. Esto evita que vuelvan sin que nadie lo note.
 */
const PAGINAS = ['/', '/examen', '/modalidades', '/cronograma', '/inscripcion', '/preguntas'];

/** Baja la página entera para que carguen las imágenes diferidas. */
async function recorrer(page: Page) {
    await page.evaluate(async () => {
        for (let y = 0; y < document.documentElement.scrollHeight; y += 600) {
            window.scrollTo(0, y);
            await new Promise((r) => setTimeout(r, 60));
        }
        window.scrollTo(0, 0);
    });
    await page.waitForTimeout(400);
}

for (const ruta of PAGINAS) {
    test.describe(`Calidad de ${ruta}`, () => {
        test('todo el texto visible pasa el contraste AA', async ({ page }) => {
            await page.emulateMedia({ reducedMotion: 'reduce' });
            await page.goto(ruta);
            await recorrer(page);

            const fallos = await page.evaluate(() => {
                // Cualquier color CSS (Tailwind 4 los da en oklch) a RGBA,
                // pintándolo en un canvas de 1 px.
                const ctx = Object.assign(document.createElement('canvas'), { width: 1, height: 1 }).getContext('2d', {
                    willReadFrequently: true,
                })!;
                const rgba = (c: string): number[] => {
                    if (!c || c === 'transparent') return [0, 0, 0, 0];
                    ctx.clearRect(0, 0, 1, 1);
                    ctx.fillStyle = '#000';
                    ctx.fillStyle = c;
                    ctx.fillRect(0, 0, 1, 1);
                    const [r, g, b, a] = ctx.getImageData(0, 0, 1, 1).data;
                    return [r, g, b, a / 255];
                };
                const mezclar = (arriba: number[], abajo: number[]) =>
                    arriba.slice(0, 3).map((v, k) => v * arriba[3] + abajo[k] * (1 - arriba[3]));
                const lum = (c: number[]) => {
                    const f = (v: number) => ((v /= 255) <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4);
                    return 0.2126 * f(c[0]) + 0.7152 * f(c[1]) + 0.0722 * f(c[2]);
                };
                // El fondo efectivo: se sube por los ancestros. Un degradado o
                // una foto por el camino no se puede medir con un color: el
                // texto sobre ellos se salta (el hero va sobre foto con velo).
                const fondo = (el: Element): number[] | null => {
                    const capas: number[][] = [];
                    for (let n: Element | null = el; n; n = n.parentElement) {
                        const cs = getComputedStyle(n);
                        if (cs.backgroundImage !== 'none') return null;
                        const c = rgba(cs.backgroundColor);
                        if (c[3] > 0) {
                            capas.push(c);
                            if (c[3] >= 1) break;
                        }
                    }
                    return capas.reverse().reduce((acc, c) => mezclar(c, acc), [255, 255, 255]);
                };

                const fallos: string[] = [];
                for (const el of document.querySelectorAll('header *, main *, footer *')) {
                    const propio = [...el.childNodes].some((n) => n.nodeType === 3 && (n.textContent ?? '').trim().length > 1);
                    if (!propio) continue;
                    const cs = getComputedStyle(el);
                    const r = el.getBoundingClientRect();
                    if (r.width === 0 || cs.visibility === 'hidden' || Number(cs.opacity) === 0) continue;
                    if (el.closest('[aria-hidden="true"], .sr-only, [hidden], [inert], .hidden')) continue;
                    // Fotos de la oficina con texto incrustado: no son texto.
                    if (el.closest('picture, figure img')) continue;
                    const f = fondo(el);
                    if (!f) continue;
                    const texto = mezclar(rgba(cs.color), f);
                    const [a, b] = [lum(texto), lum(f)].sort((x, y) => y - x);
                    const ratio = (a + 0.05) / (b + 0.05);
                    const tam = parseFloat(cs.fontSize);
                    const minimo = tam >= 24 || (tam >= 18.66 && Number(cs.fontWeight) >= 700) ? 3 : 4.5;
                    if (ratio < minimo) {
                        fallos.push(`«${(el.textContent ?? '').trim().slice(0, 40)}» ${ratio.toFixed(2)}:1 (mínimo ${minimo}:1)`);
                    }
                }
                return [...new Set(fallos)];
            });

            expect(fallos, 'Texto por debajo de AA').toEqual([]);
        });

        test('ninguna imagen del sitio se amplía ni se deforma', async ({ page }) => {
            await page.goto(ruta);
            await recorrer(page);

            const malas = await page.evaluate(() =>
                [...document.images]
                    .filter((i) => i.complete && i.naturalWidth > 0 && i.getBoundingClientRect().width > 24)
                    // Solo las que optimiza el sitio (`/_image`, `/_astro`): las
                    // que suben la oficina o la web de Letras dependen del
                    // fichero que suban, no del código.
                    .filter((i) => /\/(_image|_astro)/.test(i.currentSrc || i.src))
                    .map((i) => {
                        const r = i.getBoundingClientRect();
                        const cs = getComputedStyle(i);
                        // El ancho real del fichero servido: el descriptor «Nw» de
                        // su candidato en el srcset. `naturalWidth` no sirve: el
                        // navegador ya lo divide por la densidad elegida.
                        const servida = new URL(i.currentSrc || i.src, location.href).href;
                        const candidato = (i.getAttribute('srcset') ?? '')
                            .split(',')
                            .map((c) => c.trim().split(/\s+/))
                            .find(([u]) => new URL(u, location.href).href === servida);
                        const w = candidato?.[1]?.endsWith('w') ? parseFloat(candidato[1]) : i.naturalWidth;
                        const h = (i.naturalHeight * w) / i.naturalWidth;
                        const cubre = cs.objectFit === 'cover';
                        const escala = (cubre ? Math.max(r.width / w, r.height / h) : Math.min(r.width / w, r.height / h)) * devicePixelRatio;
                        const deformada = cs.objectFit === 'fill' && Math.abs(w / h / (r.width / r.height) - 1) > 0.03;
                        return { src: (i.currentSrc || i.src).slice(-60), escala: +escala.toFixed(2), deformada };
                    })
                    // Un 10 % de margen: el redondeo de anchos de srcset.
                    .filter((x) => x.escala > 1.1 || x.deformada)
            );

            expect(malas, 'Imágenes ampliadas más allá de su resolución o deformadas').toEqual([]);
        });

        test('tiene un solo h1', async ({ page }) => {
            await page.goto(ruta);
            await expect(page.locator('h1')).toHaveCount(1);
        });
    });
}

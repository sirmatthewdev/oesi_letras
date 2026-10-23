/**
 * Cliente de la API de contenido de Laravel.
 *
 * Dentro de Docker la base es `http://web/api/v1` —el servicio de Nginx, no
 * `localhost`, que dentro del contenedor de Astro sería el propio contenedor—.
 * Se puede sobrescribir con OESI_API para construir contra otra instancia.
 */
const BASE = import.meta.env.OESI_API ?? 'http://web/api/v1';

/**
 * Traduce una URL publica a una alcanzable desde el proceso de build.
 *
 * La API devuelve las URLs con el dominio publico —correcto: son las que
 * acabaran en el HTML y las que abre el navegador—. Pero <Image> descarga la
 * imagen DURANTE el build, y ahi `localhost` es el contenedor de Astro, no
 * Nginx: la descarga falla y el build entero se cae.
 *
 * Es el mismo problema que resolvio ForzarUrlPublica, visto del otro lado: al
 * separar el frontend hay dos redes distintas, y una URL solo puede ser
 * correcta en una de las dos. Aqui se traduce solo para descargar; Astro emite
 * despues su propia ruta, asi que el origen interno no llega al HTML.
 */
const ORIGEN_PUBLICO = import.meta.env.OESI_PUBLICO ?? 'http://localhost';
const ORIGEN_INTERNO = new URL(BASE).origin;

export function paraDescargar(url: string): string {
    if (ORIGEN_PUBLICO === ORIGEN_INTERNO) return url;
    return url.startsWith(ORIGEN_PUBLICO)
        ? ORIGEN_INTERNO + url.slice(ORIGEN_PUBLICO.length)
        : url;
}

/**
 * Token de vista previa.
 *
 * Presente solo cuando el servidor de build arranca un render de borrador. En
 * el build normal está vacío, no se manda cabecera y la API responde lo de
 * siempre: lo publicado. Nunca llega al HTML — se usa aquí, en el proceso de
 * build, y ahí se queda.
 *
 * **Variable propia, y no `OESI_VISTA_PREVIA_TOKEN`.** Esa la tiene en su
 * entorno el contenedor `build` para autenticar las peticiones de vista previa,
 * y leyéndola aquí cualquier `npm run build` lanzado a mano en ese contenedor
 * —que es como el README dice construir en producción— salía como vista previa
 * y publicaba los borradores en `dist/`. Esta solo la pone el servidor de build
 * en el render de vista previa: sin ella, el build es público por defecto.
 */
const VISTA_PREVIA = import.meta.env.OESI_RENDER_VISTA_PREVIA ?? '';

/** ¿Este build incluye borradores? Lo consultan las rutas al enumerar páginas. */
export const enVistaPrevia = VISTA_PREVIA !== '';

async function pedir<T>(ruta: string): Promise<T> {
    const url = `${BASE}${ruta}`;
    const res = await fetch(url, {
        headers: enVistaPrevia ? { 'X-Vista-Previa': VISTA_PREVIA } : {},
    });

    if (!res.ok) {
        // Falla el build en vez de generar una página vacía: un sitio estático
        // publicado con secciones en blanco es peor que un despliegue detenido.
        throw new Error(`La API respondió ${res.status} en ${url}`);
    }

    const cuerpo = await res.json();
    return cuerpo.data as T;
}

export type ItemMenu = {
    etiqueta: string;
    enlace: string | null;
    nueva_pestana: boolean;
    hijos: ItemMenu[];
};

/**
 * Identidad del sitio: lo que pintan la cabecera, el pie y la pestaña. Sale de
 * la misma fila que la página, así que el correo de la cabecera y el de la
 * página no pueden contradecirse.
 */
export type ConfiguracionSitio = {
    nombre: string;
    descripcion: string | null;
    /** El logotipo de la oficina, o el de la Facultad si no se ha subido uno. */
    logo: string | null;
    favicon: string | null;
    contacto: {
        email: string | null;
        /** El número de WhatsApp en forma legible: «+51 914 033 108». */
        telefono: string | null;
        whatsapp: string | null;
        direccion: string | null;
        horario: string | null;
    };
    facultad: { web: string | null };
    redes: Record<string, string>;
};

export const obtenerConfiguracion = () => pedir<ConfiguracionSitio>('/sitio');

export const obtenerMenu = () => pedir<ItemMenu[]>('/menu');

/**
 * La Oficina de Examen de Suficiencia en Idiomas (/oesi).
 *
 * `null` mientras no esté publicada —salvo en la vista previa—, y entonces la
 * portada dice «próximamente»: el sitio no enseña una página a medio revisar.
 *
 * Los importes llegan como números y los enlaces una sola vez: el formato lo
 * pone `lib/oesi.ts` y la página repite cada enlace donde haga falta. Los
 * textos pueden llevar `**negritas**` (ver `trozos`).
 */
export type ImagenOesi = { url: string; alt: string } | null;
export type WhatsappOesi = { numero: string; visible: string; enlace: string } | null;
export type EnlaceOesi = { nombre: string; url: string; descripcion: string | null };

export type Oesi = {
    estado: 'borrador' | 'publicado';
    nombre: string;
    institucion: string | null;
    bajada: string | null;
    contacto: { email: string | null; horario: string | null; whatsapp: WhatsappOesi };
    redes: Record<string, string>;
    estadisticas: {
        /** Sin verificar, `cifras` llega vacío salvo en la vista previa. */
        verificadas: boolean;
        cifras: { valor: number; prefijo: string; sufijo: string; etiqueta: string }[];
    };
    curso: {
        titulo: string | null;
        bajada: string | null;
        imagen: ImagenOesi;
        url: string | null;
        whatsapp: WhatsappOesi;
    };
    examen: {
        objetivo: string | null;
        destinatarios: string[];
        jurado: string | null;
        escala_intro: string | null;
        escala: {
            letra: string;
            nivel: string;
            rango: string;
            subniveles: { codigo: string; nota: string; descripcion: string | null }[];
        }[];
        vigencia_intro: string | null;
        vigencia: { nivel: string; vigencia: string }[];
        vigencia_nota: string | null;
        resultados: { aprobados: string | null; desaprobados: string | null; virtual: string | null };
        requisitos: string | null;
    };
    habilidades: {
        intro: string | null;
        items: { titulo: string; descripcion: string; icono: string | null }[];
    };
    modalidades: {
        intro: string | null;
        items: {
            modalidad: 'presencial' | 'virtual' | null;
            titulo: string;
            icono: string | null;
            subtitulo: string | null;
            introduccion: string | null;
            reglas: string[];
            nota: string | null;
            nota_tipo: 'informacion' | 'aviso';
        }[];
    };
    cronograma: {
        intro: string | null;
        imagen: ImagenOesi;
        aviso: string | null;
        formularios: EnlaceOesi[];
    };
    inscripcion: {
        intro: string | null;
        imagenes: { proceso_1: ImagenOesi; proceso_2: ImagenOesi; medio_pago: ImagenOesi };
        costos_intro: string | null;
        costos: { concepto: string; monto: number | null }[];
        pago: { banco: string | null; codigo: string | null; advertencias: string[] };
        tarifario: { concepto: string; monto: number | null; url_pago: string | null; distintivo: string | null }[];
        tarifario_nota: string | null;
        documentos: string[];
        formularios: EnlaceOesi[];
        cierre: string | null;
    };
    faqs: {
        intro: string | null;
        /** Los temas, en el orden en que se pintan. Los define el modelo. */
        temas: { clave: string; etiqueta: string }[];
        items: {
            pregunta: string;
            respuesta: string;
            lista: string[];
            /** Clave de `temas`, o null: entonces va a «Otras preguntas». */
            categoria: string | null;
        }[];
    };
};

export const obtenerOesi = () => pedir<Oesi | null>('/oesi');

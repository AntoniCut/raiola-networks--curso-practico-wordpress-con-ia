# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Tres públicos, todos por igual (ninguno es prioritario):

- **Vecinos y habituales del barrio**: café de calidad constante y cercanía; consultan carta, horarios y novedades.
- **Trabajadores de la zona**: café rápido, desayuno y pausa; buscan información práctica (carta, ubicación, horarios).
- **Entusiastas del café de especialidad**: interés por origen, tueste, catas y contenido educativo (Aprende, Orígenes, Recetas, Glosario).

## Product Purpose

Sitio web de Cafetería Raiola, cafetería de especialidad. Su propósito es triple: atraer visitas al local físico, posicionar la marca como referente de café de especialidad (origen y tueste) y construir comunidad mediante newsletter de agenda y novedades, catas, talleres y eventos.

**Aviso de simulación**: Cafetería Raiola es la simulación de una cafetería creada como proyecto práctico del curso de WordPress + Elementor + Novamira de Antony. No es un negocio real: ningún dato ficticio (reseñas, testimonios, precios, dirección, horarios, fotos) debe presentarse como real.

## Positioning

Café de especialidad con trazabilidad: historia de origen y tueste, contenido educativo y catas. (Posicionamiento simulado; en el curso sirve para practicar autoridad de marca y E-E-A-T.)

## Operating Context

- Cafetería física (simulada) + web con newsletter de agenda y novedades (páginas de confirmación y baja previstas).
- Sitio WordPress real en producción: https://cafeteria-raiola.antonydev.tech — instalación administrada por MCP (Novamira) desde este repositorio; todo cambio se registra y verifica en `diario.md`.
- Idioma del sitio: es_ES · Zona horaria: Europe/Madrid · Autor público: Antony.

## Capabilities and Constraints

- Stack decidido: WordPress 7.1.4 (PHP 8.3) con Elementor 4.3.6 + PRO Elements 4.3.2, tema hijo «Cafetería Raiola Hello Child» (Hello Elementor).
- Estructura de 31 páginas publicadas y vacías, pendientes de diseño: Inicio (portada), Carta, Nuestro café, Nosotros, Equipo, Visítanos, Reseñas, Contacto, Preguntas frecuentes, Newsletter (+confirmación/baja), Blog, Aprende, Origen y sostenibilidad, Alérgenos, Catas y talleres, Eventos privados, Café para casa, Tarjetas regalo, Trabaja con nosotros, Recetas, Orígenes, Glosario, Prensa, Accesibilidad, legales y Mapa del sitio.
- Menús: ninguno creado todavía.
- Backups: plugin propio «Cafetería Raiola Backup» (diario 03:00, retención 14 días).
- Abierto: barrio/ubicación de la cafetería (las páginas `guia-[barrio]` se excluyeron de la estructura a la espera de decidir barrio real o ficticio).

## Brand Commitments

- Nombre: **Cafetería Raiola** (con tilde) · Descripción/tagline: **Cafetería de especialidad**.
- Existe una autoridad de diseño previa registrada en Novamira: «Cafetería Raiola: origen y tueste» (se materializará en las stores globales de Elementor al construir las páginas). No se documenta ni se expande aquí; es decisión de mundo visual posterior.

## Evidence on Hand

- Sin evidencias reales de negocio (no hay reseñas, fotos, prensa ni datos de local reales); no deben fabricarse.
- Evidencia de proyecto: `diario.md` (estado del sitio verificado por MCP), `workflow/` (historial de prompts), sitio real en producción.
- Abierto: si el curso requiere contenido ficticio verosímil, decidir si se marca con un sello visible de «simulación» o se usa un estilo de ejemplo evidente.

## Product Principles

1. **Tres públicos, ninguno excluido**: cada página prioriza según su naturaleza, pero el conjunto habla por igual al vecino habitual, al trabajador de la zona y al entusiasta del café.
2. **Triple propósito**: visitas al local, autoridad de especialidad (origen y tueste) y comunidad (newsletter, agenda, catas, eventos).
3. **Honestidad de simulación**: ningún dato ficticio se presenta como real; el contenido de ejemplo se distingue del que sería real.
4. **Cambios verificados y trazables**: toda modificación se aplica por MCP, se verifica contra el sitio real y se registra en `diario.md`.

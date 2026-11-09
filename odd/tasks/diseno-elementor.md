# Diseño del sitio con Elementor (autoridad Novamira)

Objetivo: materializar el diseño activo «Cafetería Raiola: origen y tueste» en las stores globales de Elementor y construir las 31 páginas del sitio (empezando por la portada), todo por MCP Novamira con verificación contra el sitio real.
Rama: main · Entrega: ask-on-risk · Verificación: MCP (check-design, get_plugins) + Playwright contra https://cafeteria-raiola.antonydev.tech.

## Specs

- **S1 — Tokens del diseño materializados como globals de Elementor.** El diseño activo `cafeteria-raiola-origen` (authority `design`, authoritative) es la única fuente visual. Sus tokens exactos se crean o reutilizan como Global Colors y Global Fonts nativos del kit activo de Elementor, sin capa paralela y sin pegar literales en el contenido: colores `bg #F3EFE6`, `surface #FBF9F3`, `ink #1B241E`, `muted #5C574E`, `line #DCD5C6`, `accent #A65324`, variantes oscuras (`dark_bg #15211B`, `dark_surface #1C2A22`, `dark_ink #F3EFE6`, `dark_muted #B8B2A4`, `dark_line #33443A`, `dark_accent #D97A3F`); tipografía heading sans geométrica (Avenir Next/Futura, peso 700) y body sans de sistema (peso 400). Espaciado 8/16/32/64 y radios 6/12/pill se aplican en la construcción de páginas.
- **S2 — Portada «Inicio» (ID 17) construida con Elementor según el diseño.** Contenedor compartido 1120 px; hero en dos columnas (texto a la izquierda, pieza gráfica a la derecha; en móvil se apila); headline máx. 2 líneas, subtexto ~20 palabras, CTA primario visible sin scroll. Sin sombras (profundidad con hairlines y cambios de superficie), acento solo para acciones/detalles, radio 12 px consistente, una sola familia sans, secciones con aire (≥96 px), sin em-dashes, sin filas de tres tarjetas idénticas, eyebrows racionadas. Diales: variance 0.35, density 0.35, motion 0.3.
- **S3 — Honestidad de simulación.** Ningún dato ficticio (precios, dirección, horarios, reseñas, fotos) se presenta como real. Sin imágenes propias: placeholders visuales locales declarados en los colores del diseño; no se sube media a la biblioteca sin consentimiento explícito.
- **S4 — Verificación y registro.** `novamira/check-design` sobre el HTML/CSS que sirve el frontend (todo `fail` corregido antes de dar por terminado), verificación Playwright contra el sitio real (HTTP 200, secciones renderizadas), entrada en `diario.md` antes y después de cada cambio y commit del repo por unidad coherente.

## Tasks

- T1 — Sincronizar tokens en globals de Elementor (S1). Ruta: inline. Estado: pendiente.
- T2 — Construir la portada «Inicio» con Elementor (S2, S3). Ruta: inline. Estado: pendiente.
- T3 — Pre-flight, verificación real y registro en diario (S4). Ruta: inline. Estado: pendiente.

## Log

- L1 (2026-11-09): «podemos empezar por donde nos quedamos ???» — retomar el proyecto tras crear PRODUCT.md y dejar las 31 páginas vacías. Aprobación de plan: «si. adelante» (materializar la autoridad de diseño en las stores globales de Elementor y construir desde la portada).
- L2 (2026-11-09): `novamira/get-active-design` confirma diseño activo `cafeteria-raiola-origen` con authority `design` (authoritative=true), ready y sync_ready. La skill `novamira-design` obliga a materializar cada token en las stores del builder antes de referenciarlo.
- L3 (2026-11-09): T1 completado: kit de Elementor (Default Kit ID 15) sincronizado con 12 colores globales, 2 tipografías globales (titulares/cuerpo), contenedor 1120px, fondo hueso, body/titulares heredados y enlaces en tinta (el fondo del body se materializó por Custom CSS del kit con variables, porque el kit no emite reglas body). Hero de la portada (ID 17) construido con containers nativos. Mockup completo de la portada generado en `/tmp/opencode/mockup-inicio/mockup-inicio.html` para confirmar dirección; sesión sin visión (verificación por estilos computados + Chrome visible).

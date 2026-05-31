# EnsorLogs

Sitio público y tema WordPress de [ensorlogs.com](https://ensorlogs.com): bitácora sobre datos, automatización, infra, web, PropTech y lo que voy aprendiendo en el camino.

Este repo contiene lo necesario para publicar el sitio estático, generar contenido y desplegar el theme en WordPress. Notas internas, borradores y documentación operativa privada viven fuera del repo público.

---

## Qué hay aquí

| Pieza | Descripción |
| --- | --- |
| **Sitio estático** | HTML, CSS y JS en la raíz y en `en/` (español + inglés). |
| **Tema WordPress** | `wp-theme/ensorlogs` — CPT de logs y proyectos, i18n, SEO, actualizador GitHub. |
| **Motor IA** | Plugin embebido `ensorlogs-ai-engine` — Prompt Maestro, flujo RAW → HTML en el editor. |
| **Scripts** | Generación i18n, artículos, legales y parches del estático (`scripts/`). |
| **CI** | Quality Gate, release del theme y deploy a producción (`.github/workflows/`). |

---

## Estructura del proyecto

```text
ensorlogs/
├── index.html, blog.html, about.html, …   # Páginas públicas (ES)
├── en/                                    # Mirror en inglés
├── assets/                                # CSS, JS, imágenes compartidos
│   ├── css/                               # ensor-brand, ensor-reader, quiz, …
│   └── js/                                # reader, quiz, newsletter, a11y, …
├── legal/                                 # Privacidad, cookies, accesibilidad (ES)
├── content/                               # Fuente de contenido editorial
├── scripts/                               # Generadores y utilidades Python
│   └── i18n/                              # Traducciones y cuerpos de artículos EN
├── tests/                                 # Pytest: seguridad, SEO, enlaces, Lighthouse
├── wp-theme/
│   └── ensorlogs/                         # Tema WordPress completo
│       ├── inc/                           # CPT, SEO, newsletter, reader, rating, …
│       ├── assets/                        # Assets del theme (copia servida en WP)
│       ├── plugins/ensorlogs-ai-engine/   # Plugin IA embebido en el theme
│       ├── partials/                      # Fragmentos HTML reutilizables
│       └── seed-html/                     # Seed legal/i18n (sin machacar contenido vivo)
├── .github/workflows/                     # quality-gate, release-theme, deploy-production
├── docs/                                  # ARQUITECTURA.md, GUIA-ESTUDIANTES.md
└── README.md
```

---

## Flujo de trabajo

1. **Contenido** — Logs en WordPress (`ensor_article`) o HTML estático generado con `scripts/`.
2. **Theme** — Cambios en `wp-theme/ensorlogs`; versión en `style.css` y `ENSORLOGS_THEME_VERSION`.
3. **Release** — Tag `vX.Y.Z` → GitHub Actions empaqueta `ensorlogs.zip` para actualizar producción.
4. **Calidad** — El workflow **Quality Gate** corre pytest, PHP lint y Lighthouse en cada push/PR a `main`.

Documentación ampliada: [`docs/ARQUITECTURA.md`](docs/ARQUITECTURA.md).

---

## Desarrollo local

**Sitio estático**

```bash
./scripts/serve-static.sh
# Abre http://localhost:8080
```

**Tests**

```bash
pip install -r tests/requirements-dev.txt
pytest tests/ -q
```

**Theme WordPress** — Sube `wp-theme/ensorlogs.zip` (release) o sincroniza la carpeta en `wp-content/themes/ensorlogs`.

---

## Metodología

El desarrollo lo llevo yo con apoyo de **[Cursor](https://cursor.com)** como agente de codificación en el IDE: reglas en `.cursor/rules/`, skills reutilizables y contexto del repo para no improvisar cada cambio.

Aplico metodologías rápidas que se usan hoy en equipos pequeños y solo en este README las menciono explícitamente:

- **Spec-Driven Development (SDD)** — Primero el qué (brief, reglas, criterio de hecho); después el diff mínimo que lo cumple.
- **Cambios acotados** — Un objetivo por commit/release; sin refactors colaterales.
- **Verificación antes de publicar** — Quality Gate + revisión manual en staging cuando toca.

Cursor acelera implementación y repetición; las decisiones de producto, voz editorial y release siguen siendo mías.

---

## Calidad y CI

Antes de desplegar a producción, **Quality Gate** (`.github/workflows/quality-gate.yml`) ejecuta:

- Pytest — seguridad, muestra SEO y enlaces rotos
- `php -l` en PHP del theme
- Lighthouse en URLs representativas

**Release Theme** publica `ensorlogs.zip` en cada tag `v*`. **Deploy Production** sube el estático cuando corresponde.

---

## Autor

**[EnsorLogs](https://github.com/ensorlogs)** — Ensor Sánchez  
Web: [ensorlogs.com](https://ensorlogs.com) · Contacto: [ensorlogs.com/contact/](https://ensorlogs.com/contact/)

Proyecto personal. Autoría y mantenimiento: **solo EnsorLogs**.

---

## Licencia

Código del theme bajo [GPL v2 o posterior](https://www.gnu.org/licenses/gpl-2.0.html) (WordPress). Contenido editorial © EnsorLogs salvo donde se indique otra cosa.

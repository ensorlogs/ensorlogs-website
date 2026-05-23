# Calidad Ensorlogs (mínima)

Suite reducida para CI rápido. Cubre **seguridad**, **SEO y enlaces internos** (pytest en 4 páginas) y **Lighthouse** en 3 URLs.

En GitHub Actions todo va en un solo job: si **pytest** falla, no se ejecutan Gitleaks ni Lighthouse. `lychee` no corre en CI (solo manual).

Los logs y proyectos en español se sirven desde **WordPress** (`/articulos/…`, `/proyectos/…`). El HTML estático en raíz solo enlaza a producción; la muestra de artículo usa la copia EN en `en/articulos/` para validar maquetación/SEO offline.

`lychee.toml` queda para comprobación manual opcional (`lychee --config tests/lychee.toml index.html …`).

## Muestra HTML

- `index.html` — home
- `services.html` — página con hero/imágenes
- `en/articulos/wordpress-seguridad-estudiantes-2026.html` — log (reader estático EN); logs/proyectos ES viven en WordPress
- `legal/privacidad.html` — legal

## Local

```bash
python3 -m venv .venv && source .venv/bin/activate
pip install -r tests/requirements-dev.txt
pytest tests/ -q
```

Lighthouse (con servidor en 4173):

```bash
npx serve -l 4173 &
npx @lhci/cli@0.14.0 autorun --config=tests/lighthouserc.json
```

## Umbrales Lighthouse

| Categoría        | Nivel  | Mínimo |
|------------------|--------|--------|
| performance      | warn   | 0.65   |
| accessibility    | error  | 0.90   |
| best-practices   | warn   | 0.85   |
| seo              | error  | 0.90   |

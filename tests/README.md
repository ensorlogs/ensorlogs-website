# Calidad Ensorlogs (mínima)

Suite reducida para CI rápido. Cubre **seguridad**, **SEO/enlaces** en una muestra y **Lighthouse** en 3 URLs.

## Muestra HTML

- `index.html` — home
- `services.html` — página con hero/imágenes
- `articulos/wordpress-seguridad-estudiantes-2026.html` — log (reader)
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

# Suite de calidad Ensorlogs

Comprobaciones automáticas de **seguridad**, **rendimiento** y **estándares web** antes de publicar en producción.

## Qué se valida

| Capa | Herramienta | Qué mide |
|------|-------------|----------|
| HTML público | `pytest` + `html-validate` | DOCTYPE, `lang`, SEO base, `h1`, `alt`, canonical, enlaces internos |
| Seguridad estática | `pytest` + Gitleaks | `.htaccess`, ABSPATH en PHP, patrones peligrosos, secretos |
| Tema WordPress | `pytest` + `php -l` | Sintaxis PHP del theme |
| Enlaces | Lychee | Rutas rotas y carpetas bloqueadas |
| Rendimiento / a11y / SEO | Lighthouse CI | Core Web Vitals proxy, accesibilidad, best practices, SEO |

## Ejecutar en local

```bash
python3 -m venv .venv && source .venv/bin/activate
pip install -r tests/requirements-dev.txt
pytest tests/ -q

# HTML (requiere Node)
npx html-validate@9.7.0 --config tests/.htmlvalidate.json index.html about.html ...

# Enlaces
docker run --rm -v "$PWD:/work" -w /work lycheeverse/lychee:latest --config tests/lychee.toml

# Lighthouse (servidor + LHCI)
npx serve -l 4173 &
npx wait-on http://127.0.0.1:4173
npx @lhci/cli autorun --config=tests/lighthouserc.json \
  --collect.url=http://127.0.0.1:4173/index.html
```

## CI (GitHub Actions)

- `.github/workflows/quality-gate.yml` — se ejecuta en cada PR y push a `main`.
- `.github/workflows/deploy-production.yml` — solo debe ejecutarse tras un Quality Gate exitoso.

Activa **branch protection** en `main`: exige que el check `Quality Gate` pase antes de merge.

## Umbrales Lighthouse

Edita `tests/lighthouserc.json`:

- `performance`: aviso si score &lt; 0.7 (desktop en CI).
- `accessibility`, `best-practices`, `seo`: error si no alcanzan 0.9 / 0.85 / 0.9.

Los informes se guardan como artefacto `lighthouse-reports` en cada ejecución.

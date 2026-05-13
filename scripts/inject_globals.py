#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Inyector idempotente de elementos globales.

Para cada HTML del sitio (estático), asegura que:
- ``<head>`` carga ``ensor-a11y.css`` y ``ensor-cookies.css``.
- Antes de ``</body>`` se cargan ``ensor-a11y.js`` y ``ensor-cookies.js``.
- ``<meta name="ensor-cookies-url" content="…/legal/cookies.html">``
  apunta a la política de cookies con la ruta correcta.
- En el footer existe la "mini fila" con enlaces a las 4 páginas legales
  (privacidad, cookies, aviso legal, accesibilidad) + botón
  "Preferencias de cookies".
- En las plantillas que sirven logs (``blogs-details.html`` y
  ``articulos/*.html``) se inyecta además ``ensor-podcast.css`` y
  ``ensor-podcast.js``.

Es seguro re-ejecutarlo: no duplica entradas.

Ejecución::

    .venv/bin/python scripts/inject_globals.py
"""
from __future__ import annotations

import os
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

# ----- Configuración de rutas relativas por profundidad -----
# Páginas en raíz (depth=0): href relativo "assets/..."
# Páginas en articulos/ o legal/ (depth=1): href relativo "../assets/..."

GLOBAL_CSS = ['ensor-a11y.css', 'ensor-cookies.css', 'ensor-quiz.css']
GLOBAL_JS  = ['ensor-a11y.js',  'ensor-cookies.js',  'ensor-quiz.js']
LOG_CSS    = ['ensor-podcast.css']
LOG_JS     = ['ensor-podcast.js']


def rel_prefix(path: Path) -> str:
    parts = path.relative_to(ROOT).parts
    depth = len(parts) - 1  # archivo no cuenta
    return '../' * depth


def ensure_head_link(html: str, css_name: str, rel: str) -> str:
    href = f'{rel}assets/css/{css_name}'
    if f'assets/css/{css_name}' in html:
        return html
    anchor = '</head>'
    insert = f'    <link rel="stylesheet" href="{href}">\n'
    return html.replace(anchor, insert + anchor, 1)


def ensure_body_script(html: str, js_name: str, rel: str) -> str:
    src = f'{rel}assets/js/{js_name}'
    if f'assets/js/{js_name}' in html:
        return html
    snippet = f'    <script defer src="{src}"></script>\n'
    if '</body>' in html:
        return html.replace('</body>', snippet + '</body>', 1)
    return html + '\n' + snippet


def ensure_cookies_meta(html: str, rel: str) -> str:
    target = f'{rel}legal/cookies.html'
    if 'ensor-cookies-url' in html:
        # actualiza valor
        return re.sub(
            r'(<meta\s+name="ensor-cookies-url"\s+content=")[^"]+(")',
            rf'\1{target}\2',
            html,
        )
    insert = f'    <meta name="ensor-cookies-url" content="{target}">\n'
    return html.replace('</head>', insert + '</head>', 1)


LEGAL_BLOCK_ID = 'ensor-legal-row'


def build_legal_row(rel: str) -> str:
    base = f'{rel}legal'
    return (
        f'\n    <div id="{LEGAL_BLOCK_ID}" class="container max-w-[1180px] mx-auto px-4 pt-2 pb-6">\n'
        '        <nav aria-label="Enlaces legales" class="flex flex-wrap gap-x-5 gap-y-2 text-xs text-darkGray/85 dark:text-pastelGrey/70 border-t border-black/5 dark:border-white/5 pt-4">\n'
        f'            <a href="{base}/aviso-legal.html" class="hover:underline">Aviso legal</a>\n'
        f'            <a href="{base}/privacidad.html" class="hover:underline">Privacidad</a>\n'
        f'            <a href="{base}/cookies.html" class="hover:underline">Cookies</a>\n'
        f'            <a href="{base}/accesibilidad.html" class="hover:underline">Accesibilidad</a>\n'
        '            <button type="button" class="ensor-cookies-reopen" data-ensor-cookies-open>Preferencias de cookies</button>\n'
        '        </nav>\n'
        '    </div>\n'
    )


def ensure_legal_footer(html: str, rel: str) -> str:
    if f'id="{LEGAL_BLOCK_ID}"' in html:
        # Actualizamos las URLs por si cambió el depth
        new_block = build_legal_row(rel)
        return re.sub(
            r'\n\s*<div id="' + LEGAL_BLOCK_ID + r'".*?</div>\n',
            new_block,
            html,
            count=1,
            flags=re.DOTALL,
        )
    # Lo insertamos justo antes de </footer> si existe; si no, antes de </body>
    block = build_legal_row(rel)
    if '</footer>' in html:
        return html.replace('</footer>', block + '</footer>', 1)
    if '</body>' in html:
        return html.replace('</body>', block + '</body>', 1)
    return html + block


def process_file(path: Path, is_log: bool = False) -> bool:
    html = path.read_text(encoding='utf-8')
    rel = rel_prefix(path)
    original = html

    for css in GLOBAL_CSS:
        html = ensure_head_link(html, css, rel)
    if is_log:
        for css in LOG_CSS:
            html = ensure_head_link(html, css, rel)
    html = ensure_cookies_meta(html, rel)

    for js in GLOBAL_JS:
        html = ensure_body_script(html, js, rel)
    if is_log:
        for js in LOG_JS:
            html = ensure_body_script(html, js, rel)

    html = ensure_legal_footer(html, rel)

    if html != original:
        path.write_text(html, encoding='utf-8')
        return True
    return False


def main():
    targets = []
    # Páginas en raíz (depth 0)
    for name in ('index.html', 'about.html', 'blog.html', 'blog-2.html',
                 'projects.html', 'projects-details.html', 'services.html',
                 'contact.html', 'credentials.html', 'blogs-details.html'):
        p = ROOT / name
        if p.is_file():
            targets.append((p, name == 'blogs-details.html'))

    # Logs (depth 1) — son páginas de log, así que llevan el podcast
    for p in (ROOT / 'articulos').glob('*.html'):
        targets.append((p, True))

    # Proyectos (depth 1) — no llevan podcast
    proy_dir = ROOT / 'proyectos'
    if proy_dir.is_dir():
        for p in proy_dir.glob('*.html'):
            targets.append((p, False))

    # Legal (depth 1) — sin podcast
    legal_dir = ROOT / 'legal'
    if legal_dir.is_dir():
        for p in legal_dir.glob('*.html'):
            targets.append((p, False))

    changed = 0
    for path, is_log in targets:
        if process_file(path, is_log=is_log):
            print('updated:', path.relative_to(ROOT))
            changed += 1
    print(f'\nProcessed {len(targets)} files. Updated {changed}.')


if __name__ == '__main__':
    main()

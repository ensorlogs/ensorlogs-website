#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Cambia el modo por defecto del sitio estático a CLARO (blanco).

Reemplaza el script inline que ponía `dark` por defecto y deja que sea
el usuario quien active el modo oscuro mediante el botón. La preferencia
sigue persistiendo en `localStorage.theme` ("light" | "dark").

Reglas aplicadas en el `<head>` de cada HTML:

  - Si `localStorage.theme === 'dark'` → añade la clase `dark`.
  - Si no → modo claro (sin clase).
  - Si por algún motivo localStorage no se puede leer → modo claro.

También elimina la clase `dark` hardcodeada del `<html ... class="dark">`.

Ejecución::

    .venv/bin/python scripts/switch_default_light.py
"""
from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

NEW_SCRIPT = (
    "<script>(function(){try{"
    "if(localStorage.theme==='dark'){document.documentElement.classList.add('dark');}"
    "else{document.documentElement.classList.remove('dark');}"
    "}catch(e){}})();</script>"
)

# Detecta el script anterior (cualquiera de las dos variantes que estaban en uso).
SCRIPT_RE = re.compile(
    r'<script>\(function\(\)\{try\{[^<]*localStorage\.theme[^<]*?\}\)\(\);</script>',
    re.IGNORECASE,
)

# Quita la clase "dark" del <html class="...">
HTML_CLASS_RE = re.compile(
    r'(<html[^>]*?\sclass=["\'])([^"\']*)dark([^"\']*)(["\'])',
    re.IGNORECASE,
)


def _normalize_class(html: str) -> str:
    def repl(m: re.Match) -> str:
        before = m.group(2)
        after = m.group(3)
        merged = (before + after).strip()
        merged = re.sub(r'\s+', ' ', merged)
        return m.group(1) + merged + m.group(4)
    return HTML_CLASS_RE.sub(repl, html)


def process(path: Path) -> bool:
    original = path.read_text(encoding='utf-8')
    new = SCRIPT_RE.sub(NEW_SCRIPT, original, count=1)
    new = _normalize_class(new)
    if new != original:
        path.write_text(new, encoding='utf-8')
        return True
    return False


def collect_targets() -> list[Path]:
    targets: list[Path] = []
    # Raíz
    for name in ('index.html', 'about.html', 'blog.html', 'blog-2.html',
                 'projects.html', 'projects-details.html', 'services.html',
                 'contact.html', 'credentials.html', 'blogs-details.html'):
        p = ROOT / name
        if p.is_file():
            targets.append(p)
    # Subcarpetas estáticas
    for sub in ('articulos', 'proyectos', 'legal'):
        d = ROOT / sub
        if d.is_dir():
            targets.extend(sorted(d.glob('*.html')))
    # Seed-html del theme WP
    seed_root = ROOT / 'wp-theme' / 'ensorlogs' / 'seed-html'
    if seed_root.is_dir():
        targets.extend(sorted(seed_root.rglob('*.html')))
    return targets


def main() -> None:
    targets = collect_targets()
    changed = 0
    for p in targets:
        if process(p):
            print('updated:', p.relative_to(ROOT))
            changed += 1
    print(f'\nProcessed {len(targets)} files. Updated {changed}.')


if __name__ == '__main__':
    main()

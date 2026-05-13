#!/usr/bin/env python3
"""
Reemplaza el footer global por la nueva versión con voz personal:
icono @ + "¿Interesado en trabajar conmigo? Contáctame" + lead +
enlace a Calendly + copyright con "Hecho con ❤️ y WordPress",
y reinyecta el bloque legal (ensor-legal-row) con sus enlaces.

Idempotente y robusto: matchea el <footer>...</footer> completo y lo
reconstruye desde cero, así no se pueden perder secciones por errores
de regex/lookahead.
"""
from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent

TARGET_DIRS = [
    ROOT,
    ROOT / "articulos",
    ROOT / "proyectos",
    ROOT / "legal",
    ROOT / "wp-theme" / "ensorlogs" / "seed-html",
    ROOT / "wp-theme" / "ensorlogs" / "seed-html" / "articulos",
    ROOT / "wp-theme" / "ensorlogs" / "seed-html" / "proyectos",
    ROOT / "wp-theme" / "ensorlogs" / "seed-html" / "legal",
]


# Capturamos el <footer ...>...</footer> entero, marca específica
# `class="mt-24 pb-8"` para no tocar otros footers (si existieran).
FOOTER_BLOCK_RE = re.compile(
    r'<footer class="mt-24 pb-8"[^>]*>'
    r'(?P<body>.*?)'
    r'</footer>',
    re.DOTALL,
)


def detect_contact_href(body_html: str) -> str:
    m = re.search(r'href="([^"]*contact\.html)"', body_html)
    if m:
        return m.group(1)
    return "contact.html"


def detect_legal_prefix(body_html: str) -> str:
    """Devuelve el prefijo relativo usado para los enlaces legal/*.html
    (e.g. ``""``, ``"../"``, ``"../../"``). Si no encuentra ninguno,
    asume raíz."""
    m = re.search(r'href="([^"]*?)legal/aviso-legal\.html"', body_html)
    if m:
        return m.group(1)
    # Pistas alternativas: si los hrefs de contact apuntan a ../, usamos ../
    if 'href="../contact.html"' in body_html:
        return "../"
    return ""


def build_footer(contact_href: str, legal_prefix: str) -> str:
    return (
        '<footer class="mt-24 pb-8" data-aos="fade-up">\n'
        '        <div class="container text-center">\n'
        '            <div class="ensor-footer-cta" data-aos="fade-up">\n'
        '                <span class="ensor-footer-cta__icon" aria-hidden="true">\n'
        '                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">\n'
        '                        <circle cx="12" cy="12" r="4"></circle>\n'
        '                        <path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.9 7.92"></path>\n'
        '                    </svg>\n'
        '                </span>\n'
        '                <h5 class="ensor-footer-cta__heading">\n'
        f'                    ¿Interesado en trabajar conmigo? <a href="{contact_href}" class="ensor-footer-cta__link">Contáctame</a>\n'
        '                </h5>\n'
        '                <p class="ensor-footer-cta__lead">\n'
        '                    Escríbeme para un proyecto, una colaboración técnica, un taller o un curso. También me gusta hablar con gente de la comunidad y aprender de quienes saben.\n'
        '                </p>\n'
        '                <p class="ensor-footer-cta__alt">\n'
        '                    <a href="https://calendly.com/ensorlogs/30min" target="_blank" rel="noopener noreferrer" class="ensor-footer-cta__alt-link">\n'
        '                        <i class="far fa-calendar-alt" aria-hidden="true"></i>\n'
        '                        <span>¿Prefieres una llamada? Reserva 30 min en Calendly</span>\n'
        '                        <i class="fal fa-arrow-right text-xs" aria-hidden="true"></i>\n'
        '                    </a>\n'
        '                </p>\n'
        '                <p class="ensor-footer-cta__copy">\n'
        '                    &copy;2026 <a href="#top" class="text-darkGray font-medium dark:text-white">Ensorlogs</a>. Todos los derechos reservados\n'
        '                    <span class="ensor-footer-cta__love" aria-label="Hecho con amor y WordPress">\n'
        '                        · Hecho con <span class="ensor-footer-cta__heart" aria-hidden="true">❤️</span> y <a href="https://wordpress.org/" target="_blank" rel="noopener noreferrer" class="ensor-footer-cta__wp">WordPress</a>\n'
        '                    </span>\n'
        '                </p>\n'
        '            </div>\n'
        '        </div>\n'
        '    <div id="ensor-legal-row" class="container max-w-[1180px] mx-auto px-4 pt-2 pb-6">\n'
        '        <nav aria-label="Enlaces legales" class="flex flex-wrap gap-x-5 gap-y-2 text-xs text-darkGray/85 dark:text-pastelGrey/70 border-t border-black/5 dark:border-white/5 pt-4">\n'
        f'            <a href="{legal_prefix}legal/aviso-legal.html" class="hover:underline">Aviso legal</a>\n'
        f'            <a href="{legal_prefix}legal/privacidad.html" class="hover:underline">Privacidad</a>\n'
        f'            <a href="{legal_prefix}legal/cookies.html" class="hover:underline">Cookies</a>\n'
        f'            <a href="{legal_prefix}legal/accesibilidad.html" class="hover:underline">Accesibilidad</a>\n'
        '            <button type="button" class="ensor-cookies-reopen" data-ensor-cookies-open>Preferencias de cookies</button>\n'
        '        </nav>\n'
        '    </div>\n'
        '</footer>'
    )


def process_file(path: Path) -> bool:
    if path.suffix.lower() != ".html":
        return False
    text = path.read_text(encoding="utf-8")
    match = FOOTER_BLOCK_RE.search(text)
    if not match:
        return False
    body = match.group("body")
    contact_href = detect_contact_href(body)
    legal_prefix = detect_legal_prefix(body)
    new_footer = build_footer(contact_href, legal_prefix)
    new_text = text[: match.start()] + new_footer + text[match.end() :]
    if new_text == text:
        return False
    path.write_text(new_text, encoding="utf-8")
    return True


def main() -> None:
    changed = 0
    skipped = 0
    seen_dirs: set[Path] = set()
    for d in TARGET_DIRS:
        d = d.resolve()
        if not d.exists() or d in seen_dirs:
            continue
        seen_dirs.add(d)
        for path in sorted(d.glob("*.html")):
            try:
                if process_file(path):
                    changed += 1
                    print(f"  updated  {path.relative_to(ROOT)}")
                else:
                    skipped += 1
            except Exception as exc:  # noqa: BLE001
                print(f"  ERROR    {path.relative_to(ROOT)}: {exc}")
    print(f"\nDone. Updated: {changed}. Skipped (no match or identical): {skipped}.")


if __name__ == "__main__":
    main()

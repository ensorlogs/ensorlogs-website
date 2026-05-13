#!/usr/bin/env python3
"""
Aplica la voz "Ensorlogs" (documentar · conectar · ayudar) en las zonas
estratégicas del sitio: mobile menu CTA, terminal CTA del home, intros de
Servicios y Proyectos. Idempotente: si encuentra el texto ya nuevo, no toca.
"""
from __future__ import annotations

from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent

STATIC_DIRS = [
    ROOT,
    ROOT / "articulos",
    ROOT / "proyectos",
    ROOT / "legal",
]
WP_DIRS = [
    ROOT / "wp-theme" / "ensorlogs" / "seed-html",
    ROOT / "wp-theme" / "ensorlogs" / "seed-html" / "articulos",
    ROOT / "wp-theme" / "ensorlogs" / "seed-html" / "proyectos",
    ROOT / "wp-theme" / "ensorlogs" / "seed-html" / "legal",
]
WP_PARTIALS = [
    ROOT / "wp-theme" / "ensorlogs" / "partials",
]
WP_TEMPLATES = [
    ROOT / "wp-theme" / "ensorlogs" / "header.php",
]


# =========================================================
# REGLAS DE REEMPLAZO  (idempotentes — first wins)
# =========================================================
# Cada regla: lista de (literal_old, literal_new). Si ya está new, se ignora.
REPLACEMENTS: list[tuple[str, str]] = [
    # 1) Mobile menu CTA — voz personal
    (
        '<p  class="font-medium">\n                    Tienes una propuesta?\n                </p>',
        '<p  class="font-medium">\n                    ¿Hablamos?\n                </p>',
    ),
    (
        # variante con un solo espacio (por si hay variantes)
        '<p class="font-medium">\n                    Tienes una propuesta?\n                </p>',
        '<p class="font-medium">\n                    ¿Hablamos?\n                </p>',
    ),
    (
        # variante WP header.php (escapada con esc_html_e)
        "esc_html_e('Tienes una propuesta?', 'ensorlogs')",
        "esc_html_e('¿Hablamos?', 'ensorlogs')",
    ),
    (
        "esc_html_e('Tienes una propuesta', 'ensorlogs')",
        "esc_html_e('¿Hablamos', 'ensorlogs')",
    ),
    # 2) Terminal CTA del home  (label superior)
    (
        '<p class="text-sm opacity-70">\n                                TIENES UNA PROPUESTA?\n                            </p>',
        '<p class="text-sm opacity-70">\n                                ¿TIENES UNA IDEA EN MENTE?\n                            </p>',
    ),
    # 3) Servicios — intro personal (insertar bajada después del párrafo largo)
    (
        '<h5 class="text-powerBlack dark:text-pastelGrey font-semibold text-2xl xl:text-3xl mb-6">\n                        Servicios\n                    </h5>',
        '<h5 class="text-powerBlack dark:text-pastelGrey font-semibold text-2xl xl:text-3xl mb-2">\n                        Servicios\n                    </h5>\n                    <p class="text-sm font-medium text-darkGray dark:text-pastelGrey mb-5 leading-relaxed">\n                        Donde puedo darte una mano — desde tecnología hasta formación.\n                    </p>',
    ),
    # 4) Proyectos — añadir sub-texto bajo el H1 (antes de la línea decorativa)
    (
        '<h1 id="proyectos-temas-heading" class="text-xl sm:text-2xl md:text-3xl xl:text-4xl font-bold text-powerBlack dark:text-pastelGrey tracking-tight text-balance max-w-4xl leading-tight">\n                        Cómo he aplicado los stacks en la vida real\n                    </h1>\n                    <span class="ensor-tagline-rule mt-4 h-0.5 max-w-[14rem] rounded-full block" aria-hidden="true"></span>\n                    <p class="mt-4 text-sm md:text-base text-darkGray dark:text-pastelGrey max-w-3xl leading-relaxed">\n                        Aquí encontrarás implementaciones con Linux, WordPress, CRM, bases de datos, Google Workspace, automatización y otras herramientas que forman parte de mi día a día.\n                    </p>',
        '<h1 id="proyectos-temas-heading" class="text-xl sm:text-2xl md:text-3xl xl:text-4xl font-bold text-powerBlack dark:text-pastelGrey tracking-tight text-balance max-w-4xl leading-tight">\n                        Cómo he aplicado los stacks en la vida real\n                    </h1>\n                    <span class="ensor-tagline-rule mt-4 h-0.5 max-w-[14rem] rounded-full block" aria-hidden="true"></span>\n                    <p class="mt-4 text-sm md:text-base text-darkGray dark:text-pastelGrey max-w-3xl leading-relaxed">\n                        Cada proyecto es también un log: implementaciones reales con Linux, WordPress, CRM, bases de datos, Google Workspace y automatización. Lo que aprendí trabajando con clientes y equipos, escrito sin maquillaje para que puedas ver cómo lo resolvería en el tuyo.\n                    </p>',
    ),
    # 5) Blog — añadir bajada bajo "Hablemos de…"
    (
        '<h1 id="blog-temas-heading" class="text-2xl md:text-3xl xl:text-4xl font-bold text-powerBlack dark:text-pastelGrey tracking-tight">\n                        Hablemos de…\n                    </h1>\n                    <span class="ensor-tagline-rule mt-4 h-0.5 max-w-[14rem] rounded-full block" aria-hidden="true"></span>',
        '<h1 id="blog-temas-heading" class="text-2xl md:text-3xl xl:text-4xl font-bold text-powerBlack dark:text-pastelGrey tracking-tight">\n                        Hablemos de…\n                    </h1>\n                    <span class="ensor-tagline-rule mt-4 h-0.5 max-w-[14rem] rounded-full block" aria-hidden="true"></span>\n                    <p class="mt-4 text-sm md:text-base text-darkGray dark:text-pastelGrey max-w-3xl leading-relaxed">\n                        Aquí dejo escritos los <strong class="text-powerBlack dark:text-pastelGrey font-semibold">logs</strong> de lo que aprendo, pruebo y enseño. Elige un stack y entra al detalle — o ve todos abajo.\n                    </p>',
    ),
]


def gather_files() -> list[Path]:
    files: list[Path] = []
    for d in STATIC_DIRS + WP_DIRS + WP_PARTIALS:
        if not d.exists():
            continue
        for p in sorted(d.glob("*.html")):
            files.append(p)
    for t in WP_TEMPLATES:
        if t.exists():
            files.append(t)
    return files


def apply(text: str) -> tuple[str, int]:
    changed = 0
    for old, new in REPLACEMENTS:
        if new in text:
            continue
        if old in text:
            text = text.replace(old, new)
            changed += 1
    return text, changed


def main() -> None:
    updated_files = 0
    total_changes = 0
    for path in gather_files():
        original = path.read_text(encoding="utf-8")
        new, n = apply(original)
        if n == 0 or new == original:
            continue
        path.write_text(new, encoding="utf-8")
        updated_files += 1
        total_changes += n
        print(f"  updated  {path.relative_to(ROOT)}  (+{n} changes)")
    print(f"\nDone. Files: {updated_files}. Total replacements: {total_changes}.")


if __name__ == "__main__":
    main()

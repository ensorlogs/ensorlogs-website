#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Genera las páginas HTML de los Logs (Ensorlogs) en ``articulos/`` a partir de:

- Plantilla ``blogs-details.html`` (cabecera, nav, footer).
- ``.md`` en ``content/articulos/`` con front matter YAML + cuerpo Markdown / HTML.

Las páginas resultantes integran el **chrome del lector**: barra de progreso,
chip flotante con la sección actual, TOC sticky (desktop) o sheet (mobile),
badges de stacks con logo, filtro por audiencia y resaltado de palabras
clave (``<mark>``).

Requisitos: ``.venv/bin/pip install -r requirements.txt`` (PyYAML + markdown).

Ejecución::

    .venv/bin/python scripts/render_blog_articles.py
"""
from __future__ import annotations

import re
import sys
import textwrap
import unicodedata
from datetime import datetime
from pathlib import Path

try:
    import markdown
    import yaml
except ImportError:
    print(
        "Faltan dependencias. Crea un venv e instala:\n"
        "  python3 -m venv .venv && .venv/bin/pip install -r requirements.txt\n"
        "  .venv/bin/python scripts/render_blog_articles.py",
        file=sys.stderr,
    )
    raise SystemExit(1)

ROOT = Path(__file__).resolve().parents[1]
SRC = ROOT / "blogs-details.html"
OUT = ROOT / "articulos"
CONTENT = ROOT / "content" / "articulos"

# Catálogo de stacks compartido con el theme WP.
sys.path.insert(0, str(Path(__file__).resolve().parent))
from _stacks import stack_badge_html, stack_label, STACKS  # noqa: E402

_MD_EXTENSIONS = [
    "markdown.extensions.extra",
    "markdown.extensions.tables",
    "markdown.extensions.sane_lists",
    "markdown.extensions.attr_list",
]

_AUDIENCE_LABELS = {
    "context": "Contexto",
    "data": "Datos",
    "student": "Como estudiante",
    "teacher": "Como profesor",
    "professional": "Como profesional",
    "beginner": "Para empezar",
    "advanced": "Avanzado",
    "client": "Para clientes",
}


def _time_to_seconds(value) -> int:
    """Acepta "1:23", "01:23", "1:02:30", o número de segundos."""
    if value is None:
        return 0
    if isinstance(value, (int, float)):
        return int(value)
    s = str(value).strip()
    if ":" not in s:
        try:
            return int(s)
        except ValueError:
            return 0
    parts = s.split(":")
    try:
        parts = [int(p) for p in parts]
    except ValueError:
        return 0
    if len(parts) == 2:
        m, ss = parts
        return m * 60 + ss
    if len(parts) == 3:
        h, m, ss = parts
        return h * 3600 + m * 60 + ss
    return 0


def _slug_from_filename(filename: str) -> str:
    return str(filename).rsplit("/", 1)[-1].rsplit(".", 1)[0]


def _quiz_block_html(slug: str, quiz_in) -> str:
    """Genera el <section class="ensor-quiz"> con su JSON embebido.

    Espera una lista de preguntas tipo::

        quiz:
          - q: "¿Pregunta?"
            options: ["A", "B", "C", "D"]
            correct: 2
            explanation: "Por qué la C es correcta..."
    """
    import html as _html
    import json as _json

    if not isinstance(quiz_in, list) or not quiz_in:
        return ""

    questions = []
    for item in quiz_in:
        if not isinstance(item, dict):
            continue
        q = str(item.get("q") or "").strip()
        opts = item.get("options") or []
        if not q or not isinstance(opts, list) or len(opts) < 2:
            continue
        correct = item.get("correct")
        try:
            correct = int(correct)
        except (TypeError, ValueError):
            correct = 0
        correct = max(0, min(len(opts) - 1, correct))
        questions.append({
            "q": q,
            "options": [str(o) for o in opts],
            "correct": correct,
            "explanation": str(item.get("explanation") or ""),
        })

    if not questions:
        return ""

    payload = _html.escape(_json.dumps({"questions": questions}, ensure_ascii=False), quote=True)
    return (
        f'<section class="ensor-quiz" data-slug="{_html.escape(slug, quote=True)}" '
        f'data-quiz="{payload}" aria-labelledby="ensor-quiz-title-{_html.escape(slug, quote=True)}"></section>'
    )


def _log_status_badge_html(slug: str) -> str:
    import html as _html
    s = _html.escape(slug, quote=True)
    return (
        f'<span class="ensor-log-status" data-slug="{s}" aria-live="polite">'
        '<span class="ensor-log-status__dot" aria-hidden="true"></span>'
        '<span class="ensor-log-status__label">Pendiente · quiz al final</span>'
        '</span>'
    )


def _podcast_block_html(pod: dict) -> str:
    """Genera el HTML de la card "Comentario del autor" en la cabecera.

    Espera estructura tipo::

        podcast:
          src: assets/audio/log-foo.mp3
          duration: "12:34"
          title: "Comentario del autor"
          chapters:
            - { time: "0:00", title: "Intro" }
            - { time: "1:20", title: "Contexto" }
          guests:
            - { name: "Juan García", role: "Profesor de WordPress" }
    """
    if not isinstance(pod, dict):
        return ""
    src = str(pod.get("src") or "").strip()
    if not src:
        return ""

    title = str(pod.get("title") or "Escúchame contarte este log")
    duration = str(pod.get("duration") or "")
    chapters_in = pod.get("chapters") or []
    chapters = []
    for c in chapters_in:
        if isinstance(c, dict):
            t = c.get("time")
            n = c.get("title") or c.get("name") or ""
        elif isinstance(c, (list, tuple)) and len(c) >= 2:
            t, n = c[0], c[1]
        else:
            continue
        chapters.append({"time": _time_to_seconds(t), "title": str(n)})
    import json
    chapters_json = json.dumps(chapters, ensure_ascii=False)

    guests_html = ""
    guests_in = pod.get("guests") or []
    if guests_in:
        chips = []
        for g in guests_in:
            if isinstance(g, dict):
                name = str(g.get("name") or "").strip()
                role = str(g.get("role") or "").strip()
            else:
                name = str(g).strip()
                role = ""
            if not name:
                continue
            label = name + (" · " + role if role else "")
            chips.append(f'<span class="ensor-podcast-card__guest">{label}</span>')
        if chips:
            guests_html = (
                '<div class="ensor-podcast-card__guests" aria-label="Invitados">'
                + "".join(chips) +
                '</div>'
            )

    eyebrow = str(pod.get("eyebrow") or "LOG.MP3 · DIRECTOR'S CUT")
    if guests_in:
        eyebrow = str(pod.get("eyebrow") or "LOG.MP3 · CON INVITADO")

    sub_parts = []
    if duration:
        sub_parts.append(duration)
    if chapters:
        sub_parts.append(f"{len(chapters)} capítulos")
    sub_parts.append("narrado por Ensor")
    sub_text = " · ".join(sub_parts)

    return (
        f'<div class="ensor-podcast-card" data-audio="{src}" data-duration="{duration}"'
        f' data-title="{title}"'
        f' data-chapters=\'{chapters_json}\'>'
        '<button type="button" class="ensor-podcast-card__play" aria-label="Reproducir comentario del autor">'
        '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>'
        '</button>'
        '<div class="ensor-podcast-card__meta">'
        f'<span class="ensor-podcast-card__eyebrow">{eyebrow}</span>'
        f'<strong class="ensor-podcast-card__title">{title}</strong>'
        f'<span class="ensor-podcast-card__sub">{sub_text}</span>'
        f'{guests_html}'
        '</div>'
        '</div>'
    )

text = SRC.read_text(encoding="utf-8")
body_start = text.index("<body")
head = text[: text.index("</head>") + len("</head>")]
footer = text[text.index("<!--~~./ end Main Content") :]
header = text[body_start : text.index('<div class="main-content mt-28')]


def apply_rebase_for_articulos(html: str) -> str:
    pairs = (
        ('href="assets/', 'href="../assets/'),
        ('src="assets/', 'src="../assets/'),
        ('href="index.html', 'href="../index.html'),
        ('href="blog.html', 'href="../blog.html'),
        ('href="blog-2.html', 'href="../blog-2.html'),
        ('href="projects.html', 'href="../projects.html'),
        ('href="contact.html', 'href="../contact.html'),
        ('href="about.html', 'href="../about.html'),
        ('href="services.html', 'href="../services.html'),
        ('href="credentials.html', 'href="../credentials.html'),
        ('href="blogs-details.html', 'href="../blogs-details.html'),
    )
    for old, new in pairs:
        html = html.replace(old, new)
    return html


def meta_block(title, desc, keywords, canonical_path, og_title=None):
    og_title = og_title or title
    base_title = title.strip()
    for suf in (" | Ensorlogs", " | ENSOR.LOGS"):
        if base_title.endswith(suf):
            base_title = base_title[: -len(suf)].strip()
    page_title = f"{base_title} | Ensorlogs"
    return f"""    <meta name="keywords"
        content="{keywords}">
    <meta name="description"
        content="{desc}">
    <meta name="author" content="EnsorLogs">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://ensorlogs.com/{canonical_path}">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="Ensorlogs">
    <meta property="og:locale" content="es_VE">
    <meta property="og:title" content="{og_title}">
    <meta property="og:description" content="{desc}">
    <meta property="og:url" content="https://ensorlogs.com/{canonical_path}">

    <!-- Site Title
    ================================================== -->
    <title>{page_title}</title>"""


# ---------------------------------------------------------------------------
# Post-procesado HTML del cuerpo: ids en h2/h3, audiencias, marker en mark.
# ---------------------------------------------------------------------------

def _slugify(text: str) -> str:
    txt = unicodedata.normalize("NFKD", text or "")
    txt = "".join(c for c in txt if not unicodedata.combining(c))
    txt = re.sub(r"[^a-zA-Z0-9]+", "-", txt).strip("-").lower()
    return txt or "seccion"


_HEADING_RE = re.compile(r"<(h[23])([^>]*)>(.*?)</\1>", re.DOTALL | re.IGNORECASE)


def add_heading_ids(html: str) -> tuple[str, list[tuple[str, str, str]]]:
    """Inyecta `id` único en cada <h2>/<h3> y devuelve la lista (level, id, text)."""
    seen: set[str] = set()
    toc: list[tuple[str, str, str]] = []

    def repl(match: re.Match) -> str:
        tag = match.group(1).lower()
        attrs = match.group(2)
        inner = match.group(3)
        # respeta id existente
        existing = re.search(r"\sid=([\"'])([^\"']+)\1", attrs)
        if existing:
            slug = existing.group(2)
        else:
            text_only = re.sub(r"<[^>]+>", "", inner).strip()
            slug = _slugify(text_only)
            base = slug
            n = 1
            while slug in seen:
                n += 1
                slug = f"{base}-{n}"
            attrs = f' id="{slug}"' + attrs
        seen.add(slug)
        toc.append((tag, slug, re.sub(r"<[^>]+>", "", inner).strip()))
        return f"<{tag}{attrs}>{inner}</{tag}>"

    return _HEADING_RE.sub(repl, html), toc


def render_toc_html(toc: list[tuple[str, str, str]]) -> str:
    if not toc:
        return ""
    out = []
    for level, slug, text in toc:
        cls = "ensor-reader-toc__item ensor-reader-toc__item--h3" if level == "h3" else "ensor-reader-toc__item"
        out.append(
            f'<li class="{cls}"><a href="#{slug}" class="ensor-reader-toc__link" data-target="{slug}">{text}</a></li>'
        )
    return "\n                                ".join(out)


# ---------------------------------------------------------------------------
# Render principal
# ---------------------------------------------------------------------------

RELATED_CARD = """
                            <div class="blog-item group">
                                <div class="thumbnail relative overflow-hidden rounded-xl">
                                    <a href="{href}">
                                        <img src="{img}" alt="{alt}" class="w-full transition-all duration-300 group-hover:scale-110" width="800" height="450" decoding="async">
                                    </a>
                                    <div class="tags absolute right-4 top-4">
                                        <a href="blog.html?tema={tema}" class="bg-white/60 dark:bg-black/50 transition-all px-3 py-1 rounded-3xl text-darkGray dark:text-pastelGrey text-sm">{tag}</a>
                                    </div>
                                </div>
                                <div class="description py-6 px-4 pb-4">
                                    <h4 class="text-xl 2xl:text-2xl font-bold text-darkGray dark:text-pastelGrey mb-1">
                                        <a href="{href}" class="inline bg-gradient-to-r from-current from-0% to-current to-100% bg-no-repeat bg-[length:0_1px] bg-[0_95%] ease-in-out duration-200 transition-[background-size] hover:bg-[length:100%_1px]">{title}</a>
                                    </h4>
                                    <p class="line-clamp-2 !leading-normal text-sm mt-2">{excerpt}</p>
                                    <p class="mt-4"><a href="{href}" class="font-semibold text-base text-darkGray dark:text-pastelGrey">Seguir leyendo →</a></p>
                                </div>
                            </div>"""


def page(article):
    mh = head
    i0 = mh.index('<meta name="keywords"')
    i1 = mh.index("<!-- Site Favicon", i0)
    mh = mh[:i0] + meta_block(**article["meta"]) + "\n\n    " + mh[i1:]

    hero = article["hero"]
    stacks = article["stacks"]
    primary = article["primary_tema"]
    audiences_present = article["audiences"]
    podcast_html = _podcast_block_html(article.get("podcast") or {})
    slug = _slug_from_filename(article["filename"])
    quiz_html = _quiz_block_html(slug, article.get("quiz") or [])
    status_badge_html = _log_status_badge_html(slug) if quiz_html else ""

    # Cuerpo del Log: añade IDs y construye TOC.
    body_html, toc = add_heading_ids(article["html"])
    toc_html = render_toc_html(toc)

    # Stack badges (no es el primary).
    other_badges = "\n                                ".join(
        stack_badge_html(s) for s in stacks if s != primary
    )

    body = f"""
    <div class="ensor-reader-progress" aria-hidden="true">
        <div class="ensor-reader-progress__fill"></div>
    </div>
    <div class="ensor-reader-topic" role="status" aria-live="polite">
        <span class="ensor-reader-topic__dot" aria-hidden="true"></span>
        <span class="ensor-reader-topic__text"></span>
    </div>

    <article class="ensor-reader main-content mt-28 md:mt-32 lg:mt-36 xl:mt-44" data-aos="fade-up">
        <div class="container">
            <header class="ensor-reader-head max-w-[1180px] mx-auto">
                <h1 class="font-bold text-3xl lg:text-4xl xl:text-5xl text-powerBlack dark:text-pastelGrey mb-3 leading-tight">
                    {article["h1"]}
                </h1>
                <ul class="meta flex flex-wrap items-center gap-3 sm:gap-4 lg:gap-6 mb-6 text-sm lg:text-base text-darkGray dark:text-pastelGrey">
                    <li>
                        {stack_badge_html(primary, base_href="blog.html?tema=", extra_class="ensor-reader-stack--xl")}
                    </li>
                    <li>
                        <i class="fal fa-clock" aria-hidden="true"></i>
                        {article["date"]}
                    </li>
                    <li>
                        <i class="fal fa-map-marker-alt" aria-hidden="true"></i>
                        Caracas, Venezuela
                    </li>
                    <li>
                        <i class="fal fa-user" aria-hidden="true"></i>
                        EnsorLogs
                    </li>
                    <li>
                        <i class="fal fa-book-open" aria-hidden="true"></i>
                        {article["read_min"]} min de lectura
                    </li>
                    {f'<li>{status_badge_html}</li>' if status_badge_html else ''}
                </ul>

                <div class="ensor-reader-headcard">
                    <div class="ensor-reader-stacks" aria-label="Stacks tratados en este log">
                                {stack_badge_html(primary) if primary else ""}
                                {other_badges}
                    </div>
                    {('<nav class="ensor-reader-aud" aria-label="Saltar a sección del log"></nav>') if audiences_present else ''}
                </div>
                {podcast_html}
            </header>

            <div class="ensor-reader-hero max-w-screen-lg mx-auto mt-2 mb-8 md:mb-10">
                <img
                    src="{hero["src"]}"
                    alt="{hero["alt"]}"
                    width="1600" height="900"
                    decoding="async" loading="eager" fetchpriority="high"
                    class="ensor-article-hero rounded-xl xl:rounded-2xl w-full object-cover object-center">
            </div>

            <div class="ensor-reader-layout max-w-[1180px] mx-auto">
                <aside class="ensor-reader-toc hidden lg:block" aria-label="Índice del log">
                    <p class="ensor-reader-toc__title">En este log</p>
                    <ul class="ensor-reader-toc__list">
                                {toc_html}
                    </ul>
                </aside>
                <div>
                    <div class="ensor-reader-body entry-content ensor-wp-content max-w-none">
                        {body_html}
                    </div>

                    {quiz_html}

                    <footer class="ensor-reader-footer mt-10 grid md:grid-cols-2 gap-4 md:gap-6 items-center">
                        <div class="ensor-reader-stacks-block flex flex-wrap items-center gap-3" aria-label="Stacks del log">
                            <span class="ensor-reader-stacks-label">Stacks usados:</span>
                            <div class="ensor-reader-stacks flex flex-wrap gap-2">
                                {stack_badge_html(primary) if primary else ""}
                                {other_badges}
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 md:justify-end">
                            <a href="blog.html" class="ensor-cta-hablemos inline-flex items-center justify-center shrink-0 font-semibold py-2 px-5 md:py-2.5 md:px-7 leading-snug rounded-full no-underline"><span>Más logs</span></a>
                            <a href="contact.html" class="ensor-cta-hablemos inline-flex items-center justify-center shrink-0 font-semibold py-2 px-5 md:py-2.5 md:px-7 leading-snug rounded-full no-underline"><span>Hablemos</span></a>
                        </div>
                    </footer>

                    {article["recent_posts_block"]}
                </div>
            </div>
        </div>
    </article>

    <button type="button" class="ensor-reader-toc-toggle lg:hidden" aria-label="Ver índice">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 6h13M4 12h13M4 18h13"/>
            <circle cx="20" cy="6" r="1.2" fill="currentColor"/>
            <circle cx="20" cy="12" r="1.2" fill="currentColor"/>
            <circle cx="20" cy="18" r="1.2" fill="currentColor"/>
        </svg>
    </button>
    <div class="ensor-reader-toc-sheet" role="dialog" aria-label="Índice del log">
        <div class="ensor-reader-toc-sheet__panel">
            <p class="ensor-reader-toc__title">En este log</p>
            <ul class="ensor-reader-toc__list">
                                {toc_html}
            </ul>
        </div>
    </div>
"""
    out = mh + "\n" + header + body + footer
    out = apply_rebase_for_articulos(out)
    OUT.mkdir(parents=True, exist_ok=True)
    (OUT / article["filename"]).write_text(out, encoding="utf-8")


def _audiences_from_html(html: str) -> list[str]:
    """Extrae lista única de audiencias presentes en el HTML del cuerpo."""
    found: set[str] = set()
    for m in re.finditer(r'data-aud=(["\'])\s*([^"\']+)\s*\1', html, re.I):
        for part in re.split(r"[\s,]+", m.group(2).lower()):
            part = part.strip()
            if part:
                found.add(part)
    return sorted(found)


def _read_minutes(html: str) -> int:
    text = re.sub(r"<[^>]+>", " ", html)
    words = len(re.findall(r"\w+", text))
    return max(1, (words + 219) // 220)


def _parse_markdown_file(path: Path) -> dict:
    raw = path.read_text(encoding="utf-8")
    if not raw.lstrip().startswith("---"):
        raise ValueError(f"Se esperaba front matter YAML en {path}")
    parts = raw.split("---", 2)
    if len(parts) < 3:
        raise ValueError(f"Front matter o cuerpo incompleto en {path}")
    fm = yaml.safe_load(parts[1])
    if not isinstance(fm, dict):
        raise ValueError(f"Front matter inválido en {path}")
    body_raw = textwrap.dedent(parts[2]).strip()
    body_html = markdown.markdown(body_raw, extensions=_MD_EXTENSIONS, output_format="html")

    # `stacks:` puede venir como lista o derivarse de los antiguos `tags`.
    stacks_in = fm.get("stacks") or []
    if not stacks_in and fm.get("tags"):
        for t in fm["tags"]:
            if isinstance(t, dict) and "slug" in t:
                stacks_in.append(str(t["slug"]))
            elif isinstance(t, (list, tuple)) and len(t) == 2:
                stacks_in.append(str(t[0]))
    stacks = [s.lower() for s in stacks_in]

    return {
        "order": int(fm.get("order") or 999),
        "filename": str(fm["filename"]),
        "primary_tema": str(fm["primary_tema"]).lower(),
        "pill": str(fm.get("pill") or stack_label(str(fm["primary_tema"]))),
        "h1": str(fm["h1"]),
        "date": str(fm["date"]),
        "stacks": stacks,
        "audiences": _audiences_from_html(body_html),
        "read_min": _read_minutes(body_html),
        "meta": {
            "title": str(fm["meta_title"]),
            "desc": str(fm["meta_desc"]),
            "keywords": str(fm["meta_keywords"]),
            "canonical_path": str(fm["canonical_path"]),
        },
        "hero": {"src": str(fm["hero_src"]), "alt": str(fm["hero_alt"])},
        "podcast": fm.get("podcast") or {},
        "quiz": fm.get("quiz") or [],
        "html": body_html,
    }


def load_articles() -> list:
    if not CONTENT.is_dir():
        raise FileNotFoundError(f"No existe la carpeta {CONTENT}")
    paths = sorted(CONTENT.glob("*.md"))
    paths = [p for p in paths if p.name.lower() != "readme.md"]
    if not paths:
        raise FileNotFoundError(f"No hay .md en {CONTENT}")
    rows = []
    for path in paths:
        rows.append(_parse_markdown_file(path))
    rows.sort(key=lambda a: (a["order"], a["filename"]))
    return rows


def build_related_pair(a, b):
    return (
        RELATED_CARD.format(
            href=a["filename"],
            img=a["hero"]["src"].replace("w=1600", "w=900"),
            alt=a["hero"]["alt"],
            tema=a["primary_tema"],
            tag=stack_label(a["primary_tema"]),
            title=a["h1"][:72] + ("…" if len(a["h1"]) > 72 else ""),
            excerpt="Log de Ensorlogs — bitácora técnica y pedagógica.",
        )
        + RELATED_CARD.format(
            href=b["filename"],
            img=b["hero"]["src"].replace("w=1600", "w=900"),
            alt=b["hero"]["alt"],
            tema=b["primary_tema"],
            tag=stack_label(b["primary_tema"]),
            title=b["h1"][:72] + ("…" if len(b["h1"]) > 72 else ""),
            excerpt="Log de Ensorlogs — bitácora técnica y pedagógica.",
        )
    )


def recent_posts_section(related_inner_html: str) -> str:
    if not related_inner_html.strip():
        return ""
    return f"""                    <div class="recent-posts mt-12">
                        <h3 class="text-xl lg:text-2xl text-darkGray dark:text-pastelGrey font-bold mb-4">
                            Otros logs
                        </h3>
                        <div class="grid sm:grid-cols-2 gap-6 *:p-2 *:rounded-xl xl:*:rounded-3xl *:bg-gradient-to-b *:from-milkWhite *:to-seashell *:dark:from-metalBlack *:dark:to-oilBlack *:border *:border-gray-100 dark:*:border-white/5">
                            {related_inner_html}
                        </div>
                    </div>
"""


def main():
    articles = load_articles()
    n = len(articles)
    for i, art in enumerate(articles):
        if n >= 3:
            others = [articles[(i + 1) % n], articles[(i + 2) % n]]
            art["recent_posts_block"] = recent_posts_section(
                build_related_pair(others[0], others[1])
            )
        elif n == 2:
            others = [articles[(i + 1) % n]]
            art["recent_posts_block"] = recent_posts_section(
                build_related_pair(others[0], others[0])
            )
        else:
            art["recent_posts_block"] = ""
        page(art)
    print(f"Wrote {n} logs to {OUT}")

    # Asegura que los HTML recién generados llevan a11y/cookies/podcast +
    # enlaces legales en el footer. Es idempotente, así que es seguro.
    try:
        import inject_globals  # type: ignore
        inject_globals.main()
    except Exception as e:  # pragma: no cover
        print("inject_globals omitido:", e)


if __name__ == "__main__":
    main()

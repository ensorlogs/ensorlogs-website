#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Genera las páginas HTML del blog en ``articulos/`` a partir de:

- Plantilla ``blogs-details.html`` (cabecera, nav, footer).
- Uno o más ``.md`` en ``content/articulos/`` con front matter YAML + cuerpo (Markdown y/o HTML).

Requisitos: ``pip install -r requirements.txt`` (PyYAML + markdown). Ejemplo::

    .venv/bin/python scripts/render_blog_articles.py

La fuente de verdad del texto es **Markdown**; los ``articulos/*.html`` son salida regenerable.
"""
from __future__ import annotations

import sys
import textwrap
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

_MD_EXTENSIONS = [
    "markdown.extensions.extra",
    "markdown.extensions.tables",
    "markdown.extensions.sane_lists",
]

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
    return f"""    <meta name="keywords"
        content="{keywords}">
    <meta name="description"
        content="{desc}">
    <meta name="author" content="Ensor Sánchez · Ensorlogs">
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
    <title>{title} | Ensorlogs</title>"""


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


def comments_short():
    return """
                    <div class="comments">
                        <h3 class="text-xl lg:text-2xl text-darkGray dark:text-pastelGrey font-bold mb-4">
                            Comentarios
                        </h3>
                        <p class="text-regular text-darkGray dark:text-pastelGrey max-w-2xl">
                            ¿Te resuena algo de lo que cuento desde Caracas? Cuando active el formulario público, podrás dejar dudas o experiencias. Mientras tanto, puedes escribirme por <a href="contact.html" class="underline font-medium text-powerBlack dark:text-pastelGrey">contacto</a>.
                        </p>
                    </div>"""


def tag_links(slugs_labels):
    parts = []
    for slug, label in slugs_labels:
        parts.append(
            f'<a href="blog.html?tema={slug}" class="inline-flex transition-all duration-200 border border-black/5 dark:border-white/5 hover:border-white hover:bg-white dark:hover:bg-black dark:hover:text-pastelGrey px-4 py-2 rounded-3xl text-sm">{label}</a>'
        )
    return "\n                            ".join(parts)


def page(article):
    mh = head
    i0 = mh.index('<meta name="keywords"')
    i1 = mh.index("<!-- Site Favicon", i0)
    mh = mh[:i0] + meta_block(**article["meta"]) + "\n\n    " + mh[i1:]
    hero = article["hero"]
    body = f"""
    <div class="main-content mt-28 md:mt-32 lg:mt-36 xl:mt-48">
        <div class="container space-y-6">
           <div class="blog-details-wrapper" data-aos="fade-up">
                <div class="details-header max-w-[870px] mx-auto">
                    <h1 class="font-bold text-3xl lg:text-4xl text-darkGray dark:text-pastelGrey mb-2">
                        {article["h1"]}
                    </h1>
                    <ul class="meta flex flex-wrap items-center gap-3 sm:gap-4 lg:gap-6 mb-6 text-sm lg:text-base">
                        <li>
                            <a href="blog.html?tema={article["primary_tema"]}" class="bg-white dark:bg-black/50 transition-all inline-flex px-6 py-2 rounded-3xl text-darkGray dark:text-pastelGrey">
                                {article["pill"]}
                            </a>
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
                            Ensor Sánchez · ENSOR.LOGS
                        </li>
                    </ul>
                </div>
                <div class="thumbnail mb-12 max-w-screen-lg mx-auto">
                    <img src="{hero["src"]}" alt="{hero["alt"]}" width="1600" height="900" decoding="async" class="rounded-xl xl:rounded-2xl max-md:h-60 w-full object-cover object-center">
                </div>
                <div class="*:max-w-[870px] *:mx-auto space-y-12">
                    <div class="details-body text-darkGray dark:text-pastelGrey leading-relaxed space-y-6">
                        {article["html"]}
                    </div>
                    <div class="meta-info grid md:grid-cols-2 gap-4">
                        <div class="tags flex flex-wrap items-center gap-4 text-sm">
                            <span class="font-semibold text-powerBlack dark:text-pastelGrey">Temas</span>
                            {article["tag_links"]}
                        </div>
                        <div class="share flex flex-wrap items-center md:justify-end gap-4 text-sm text-darkGray dark:text-pastelGrey">
                            <a href="blog.html" class="underline font-medium">Volver al blog</a>
                            <a href="contact.html" class="underline font-medium">Hablemos</a>
                        </div>
                    </div>
                    {comments_short()}
                    <div class="recent-posts">
                        <h3 class="text-xl lg:text-2xl text-darkGray dark:text-pastelGrey font-bold mb-4">
                            Otros artículos
                        </h3>
                        <div class="grid sm:grid-cols-2 gap-6 *:p-2 *:rounded-xl xl:*:rounded-3xl *:bg-gradient-to-b *:from-milkWhite *:to-seashell *:dark:from-metalBlack *:dark:to-oilBlack *:border *:border-gray-100 dark:*:border-white/5">
                            {article["related_html"]}
                        </div>
                    </div>
                </div>
           </div>
        </div>
    </div>
"""
    out = mh + "\n" + header + body + footer
    out = apply_rebase_for_articulos(out)
    OUT.mkdir(parents=True, exist_ok=True)
    (OUT / article["filename"]).write_text(out, encoding="utf-8")


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
    tags_in = fm.get("tags") or []
    pairs = []
    for t in tags_in:
        if isinstance(t, dict) and "slug" in t and "label" in t:
            pairs.append((str(t["slug"]), str(t["label"])))
        elif isinstance(t, (list, tuple)) and len(t) == 2:
            pairs.append((str(t[0]), str(t[1])))
    return {
        "order": int(fm.get("order") or 999),
        "filename": str(fm["filename"]),
        "primary_tema": str(fm["primary_tema"]),
        "pill": str(fm["pill"]),
        "h1": str(fm["h1"]),
        "date": str(fm["date"]),
        "meta": {
            "title": str(fm["meta_title"]),
            "desc": str(fm["meta_desc"]),
            "keywords": str(fm["meta_keywords"]),
            "canonical_path": str(fm["canonical_path"]),
        },
        "hero": {"src": str(fm["hero_src"]), "alt": str(fm["hero_alt"])},
        "tag_links": tag_links(pairs),
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


def build_related(current_fn, other):
    del current_fn
    a, b = other
    return (
        RELATED_CARD.format(
            href=a["filename"],
            img=a["hero"]["src"].replace("w=1600", "w=900"),
            alt=a["hero"]["alt"],
            tema=a["primary_tema"],
            tag=a["pill"],
            title=a["h1"][:72] + ("…" if len(a["h1"]) > 72 else ""),
            excerpt="Artículo ENSOR.LOGS — lectura larga con enfoque Venezuela y Caracas.",
        )
        + RELATED_CARD.format(
            href=b["filename"],
            img=b["hero"]["src"].replace("w=1600", "w=900"),
            alt=b["hero"]["alt"],
            tema=b["primary_tema"],
            tag=b["pill"],
            title=b["h1"][:72] + ("…" if len(b["h1"]) > 72 else ""),
            excerpt="Bitácora técnica y pedagógica — Ensor Sánchez.",
        )
    )


def main():
    articles = load_articles()
    n = len(articles)
    for i, art in enumerate(articles):
        others = [articles[(i + 1) % n], articles[(i + 2) % n]]
        art["related_html"] = build_related(art["filename"], others)
        page(art)
    print("Wrote", n, "articles to", OUT)


if __name__ == "__main__":
    main()

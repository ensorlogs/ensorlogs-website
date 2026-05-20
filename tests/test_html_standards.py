"""Estándares HTML públicos: SEO base, accesibilidad y rutas internas."""

from __future__ import annotations

import re
import warnings
from html.parser import HTMLParser
from pathlib import Path

import pytest

from conftest import REPO_ROOT, iter_public_html

BLOCKED_HREF_PREFIXES = (
    "/scripts/",
    "/docs/",
    "/content/",
    "/src/",
    "/wp-theme/",
    "scripts/",
    "docs/",
    "content/",
    "wp-theme/",
)


class PageAnalyzer(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.has_doctype_hint = False
        self.html_lang: str | None = None
        self.charset = False
        self.viewport = False
        self.title = ""
        self.in_title = False
        self.description = False
        self.canonical = False
        self.h1_count = 0
        self.heading_count = 0
        self.imgs_missing_alt: list[str] = []
        self.hrefs: list[str] = []

    def handle_decl(self, decl: str) -> None:
        if "html" in decl.lower():
            self.has_doctype_hint = True

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        attr = {k: (v or "") for k, v in attrs}
        if tag == "html":
            self.html_lang = attr.get("lang") or self.html_lang
        if tag == "meta":
            http_equiv = attr.get("http-equiv", "").lower()
            name = attr.get("name", "").lower()
            prop = attr.get("property", "").lower()
            if attr.get("charset") or (http_equiv == "content-type" and "charset" in attr.get("content", "").lower()):
                self.charset = True
            if name == "viewport" or "viewport" in attr.get("content", "").lower():
                self.viewport = True
            if name == "description" or prop == "og:description":
                self.description = True
        if tag == "link" and attr.get("rel", "").lower() == "canonical":
            self.canonical = True
        if tag == "title":
            self.in_title = True
        if tag in {"h1", "h2", "h3", "h4", "h5", "h6"}:
            self.heading_count += 1
            if tag == "h1":
                self.h1_count += 1
        if tag == "img" and "alt" not in attr:
            src = attr.get("src", "(sin src)")
            self.imgs_missing_alt.append(src)
        if tag == "a" and attr.get("href"):
            self.hrefs.append(attr["href"])

    def handle_endtag(self, tag: str) -> None:
        if tag == "title":
            self.in_title = False

    def handle_data(self, data: str) -> None:
        if self.in_title:
            self.title += data


def analyze_html(path: Path) -> PageAnalyzer:
    text = path.read_text(encoding="utf-8", errors="replace")
    parser = PageAnalyzer()
    if text.lstrip().lower().startswith("<!doctype html"):
        parser.has_doctype_hint = True
    parser.feed(text)
    return parser


def rel_href_target(href: str, page: Path) -> Path | None:
    if href.startswith(("http://", "https://", "mailto:", "tel:", "#", "javascript:")):
        return None
    clean = href.split("#", 1)[0].split("?", 1)[0]
    if not clean or clean.startswith("//"):
        return None
    base = page.parent
    if clean.startswith("/"):
        return REPO_ROOT / clean.lstrip("/")
    return (base / clean).resolve()


@pytest.mark.parametrize("html_path", iter_public_html(), ids=lambda p: p.relative_to(REPO_ROOT).as_posix())
def test_public_html_document_basics(html_path: Path) -> None:
    rel = html_path.read_text(encoding="utf-8", errors="replace")
    parser = analyze_html(html_path)

    assert parser.has_doctype_hint, f"{html_path}: falta <!DOCTYPE html>"
    assert parser.html_lang, f"{html_path}: <html> sin atributo lang"
    assert parser.charset, f"{html_path}: falta meta charset"
    assert parser.viewport, f"{html_path}: falta meta viewport"
    assert parser.title.strip(), f"{html_path}: <title> vacío"
    assert parser.description, f"{html_path}: falta meta description u og:description"
    assert parser.heading_count >= 1, f"{html_path}: falta jerarquía de encabezados (h1–h6)"
    if parser.h1_count == 0:
        warnings.warn(f"{html_path}: sin <h1> (recomendado para SEO y accesibilidad)", UserWarning, stacklevel=1)
    assert not parser.imgs_missing_alt, (
        f"{html_path}: imágenes sin atributo alt: {', '.join(parser.imgs_missing_alt[:5])}"
    )
    assert "http://" not in rel or "ensorlogs.com" in rel, (
        f"{html_path}: enlaces inseguros http:// detectados en el HTML"
    )


@pytest.mark.parametrize("html_path", iter_public_html(), ids=lambda p: p.relative_to(REPO_ROOT).as_posix())
def test_public_html_canonical_on_primary_pages(html_path: Path) -> None:
    parser = analyze_html(html_path)
    if html_path.name in {"blog-2.html"}:
        pytest.skip("página secundaria de listado")
    assert parser.canonical, f"{html_path}: falta <link rel=\"canonical\">"


@pytest.mark.parametrize("html_path", iter_public_html(), ids=lambda p: p.relative_to(REPO_ROOT).as_posix())
def test_internal_links_and_blocked_paths(html_path: Path) -> None:
    parser = analyze_html(html_path)
    broken: list[str] = []
    blocked: list[str] = []

    for href in parser.hrefs:
        lower = href.lower()
        if any(lower.startswith(prefix) or f"/{prefix}" in lower for prefix in BLOCKED_HREF_PREFIXES):
            blocked.append(href)
        target = rel_href_target(href, html_path)
        if target is None:
            continue
        try:
            target.relative_to(REPO_ROOT)
        except ValueError:
            continue
        if target.suffix in {".html", ""} and not target.exists():
            broken.append(f"{href} -> {target.relative_to(REPO_ROOT)}")

    assert not blocked, f"{html_path}: enlaces a rutas bloqueadas: {blocked}"
    assert not broken, f"{html_path}: enlaces internos rotos: {broken}"

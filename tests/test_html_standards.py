"""SEO y enlaces internos en una muestra pequeña de páginas públicas."""

from __future__ import annotations

from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import unquote

import pytest

from conftest import REPO_ROOT, iter_quality_html

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
        self.imgs_missing_alt: list[str] = []
        self.hrefs: list[str] = []
        self.srcs: list[str] = []

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
            if attr.get("charset") or (
                http_equiv == "content-type" and "charset" in attr.get("content", "").lower()
            ):
                self.charset = True
            if name == "viewport" or "viewport" in attr.get("content", "").lower():
                self.viewport = True
            if name == "description" or prop == "og:description":
                self.description = True
        if tag == "link" and attr.get("rel", "").lower() == "canonical":
            self.canonical = True
        if tag == "title":
            self.in_title = True
        if tag == "img" and "alt" not in attr:
            self.imgs_missing_alt.append(attr.get("src", "(sin src)"))
        if tag == "a" and attr.get("href"):
            self.hrefs.append(attr["href"])
        if tag in {"img", "script", "source", "video", "audio"} and attr.get("src"):
            self.srcs.append(attr["src"])
        if tag == "link" and attr.get("href") and "stylesheet" in attr.get("rel", "").lower():
            self.srcs.append(attr["href"])

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
    clean = unquote(href.split("#", 1)[0].split("?", 1)[0])
    if not clean or clean.startswith("//"):
        return None
    base = page.parent
    if clean.startswith("/"):
        return REPO_ROOT / clean.lstrip("/")
    return (base / clean).resolve()


@pytest.mark.parametrize("html_path", iter_quality_html(), ids=lambda p: p.relative_to(REPO_ROOT).as_posix())
def test_quality_sample_page(html_path: Path) -> None:
    """SEO base + enlaces internos en páginas representativas."""
    raw = html_path.read_text(encoding="utf-8", errors="replace")
    parser = analyze_html(html_path)

    assert parser.has_doctype_hint, "falta <!DOCTYPE html>"
    assert parser.html_lang, "<html> sin lang"
    assert parser.charset, "falta meta charset"
    assert parser.viewport, "falta meta viewport"
    assert parser.title.strip(), "<title> vacío"
    assert parser.description, "falta meta description u og:description"
    assert parser.canonical, 'falta <link rel="canonical">'
    assert not parser.imgs_missing_alt, f"img sin alt: {parser.imgs_missing_alt[:3]}"
    assert "http://" not in raw or "ensorlogs.com" in raw, "enlaces http:// inseguros"

    broken: list[str] = []
    blocked: list[str] = []
    for ref in parser.hrefs + parser.srcs:
        lower = ref.lower()
        if any(lower.startswith(prefix) or f"/{prefix}" in lower for prefix in BLOCKED_HREF_PREFIXES):
            blocked.append(ref)
        target = rel_href_target(ref, html_path)
        if target is None:
            continue
        try:
            target.relative_to(REPO_ROOT)
        except ValueError:
            continue
        if not target.exists():
            broken.append(f"{ref} -> {target.relative_to(REPO_ROOT)}")

    assert not blocked, f"rutas bloqueadas: {blocked}"
    assert not broken, f"enlaces rotos: {broken}"

"""Fixtures compartidas para la suite de calidad Ensorlogs."""

from __future__ import annotations

from pathlib import Path

import pytest

REPO_ROOT = Path(__file__).resolve().parents[1]
THEME_ROOT = REPO_ROOT / "wp-theme" / "ensorlogs"

PUBLIC_HTML_DIRS = (
    REPO_ROOT,
    REPO_ROOT / "articulos",
    REPO_ROOT / "proyectos",
    REPO_ROOT / "legal",
)

PUBLIC_HTML_SKIP = frozenset(
    {
        "blogs-details.html",  # plantilla genérica sin contenido publicado
        "projects-details.html",
    }
)


def iter_public_html() -> list[Path]:
    pages: list[Path] = []
    for directory in PUBLIC_HTML_DIRS:
        for path in sorted(directory.glob("*.html")):
            if path.name in PUBLIC_HTML_SKIP:
                continue
            pages.append(path)
    return pages


@pytest.fixture(scope="session")
def repo_root() -> Path:
    return REPO_ROOT


@pytest.fixture(scope="session")
def public_html_pages() -> list[Path]:
    return iter_public_html()


@pytest.fixture(scope="session")
def theme_php_files() -> list[Path]:
    return sorted(THEME_ROOT.rglob("*.php"))

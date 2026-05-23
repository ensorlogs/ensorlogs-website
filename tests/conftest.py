"""Fixtures compartidas para la suite de calidad Ensorlogs."""

from __future__ import annotations

from pathlib import Path

import pytest

REPO_ROOT = Path(__file__).resolve().parents[1]
THEME_ROOT = REPO_ROOT / "wp-theme" / "ensorlogs"

# Muestra representativa del sitio (CI rápido; no recorre cada HTML).
def _sample_page(*candidates: str) -> Path:
    """Primera ruta existente; si ninguna, la primera (fallará el assert con lista clara)."""
    paths = [REPO_ROOT / c for c in candidates]
    for path in paths:
        if path.is_file():
            return path
    return paths[0]


QUALITY_SAMPLE_HTML = (
    _sample_page("index.html"),
    _sample_page("services.html"),
    _sample_page(
        "en/articulos/wordpress-seguridad-estudiantes-2026.html",
        "articulos/wordpress-seguridad-estudiantes-2026.html",
    ),
    _sample_page("legal/privacidad.html", "en/legal/privacidad.html"),
)

PUBLIC_HTML_SKIP = frozenset(
    {
        "blogs-details.html",
        "projects-details.html",
    })


def iter_quality_html() -> list[Path]:
    missing = [p.relative_to(REPO_ROOT).as_posix() for p in QUALITY_SAMPLE_HTML if not p.is_file()]
    assert not missing, f"Faltan páginas de la muestra CI: {missing}"
    return list(QUALITY_SAMPLE_HTML)


@pytest.fixture(scope="session")
def repo_root() -> Path:
    return REPO_ROOT


@pytest.fixture(scope="session")
def quality_html_pages() -> list[Path]:
    return iter_quality_html()


@pytest.fixture(scope="session")
def theme_php_files() -> list[Path]:
    return sorted(THEME_ROOT.rglob("*.php"))

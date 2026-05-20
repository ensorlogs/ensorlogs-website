"""Fixtures compartidas para la suite de calidad Ensorlogs."""

from __future__ import annotations

from pathlib import Path

import pytest

REPO_ROOT = Path(__file__).resolve().parents[1]
THEME_ROOT = REPO_ROOT / "wp-theme" / "ensorlogs"

# Muestra representativa del sitio (CI rápido; no recorre cada HTML).
QUALITY_SAMPLE_HTML = (
    REPO_ROOT / "index.html",
    REPO_ROOT / "services.html",
    REPO_ROOT / "articulos" / "wordpress-seguridad-estudiantes-2026.html",
    REPO_ROOT / "legal" / "privacidad.html",
)

PUBLIC_HTML_SKIP = frozenset(
    {
        "blogs-details.html",
        "projects-details.html",
    })


def iter_quality_html() -> list[Path]:
    missing = [p for p in QUALITY_SAMPLE_HTML if not p.is_file()]
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

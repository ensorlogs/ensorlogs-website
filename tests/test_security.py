"""Comprobaciones estáticas de seguridad en repo y tema WordPress."""

from __future__ import annotations

import re
from pathlib import Path

import pytest

from conftest import REPO_ROOT, THEME_ROOT

SECRET_PATTERNS = [
    (re.compile(r"sk_live_[0-9a-zA-Z]{16,}"), "Stripe live secret key"),
    (re.compile(r"AKIA[0-9A-Z]{16}"), "AWS access key"),
    (re.compile(r"(?i)(api[_-]?key|secret|password)\s*=\s*['\"][^'\"]{8,}['\"]"), "credencial embebida"),
    (re.compile(r"-----BEGIN (RSA |EC )?PRIVATE KEY-----"), "clave privada PEM"),
]

HTACCESS_REQUIRED = (
    "X-Content-Type-Options",
    "X-Frame-Options",
    "Referrer-Policy",
    "Strict-Transport-Security",
    "Options -Indexes",
)

HTACCESS_BLOCKED_DIRS = ("docs", "content", "scripts", "src", "wp-theme")

DANGEROUS_PHP = [
    (re.compile(r"\beval\s*\("), "eval()"),
    (re.compile(r"\b(shell_exec|exec|passthru|system|popen)\s*\("), "ejecución de comandos"),
    (re.compile(r"\bunserialize\s*\(\s*\$_"), "unserialize sobre entrada"),
]

TEXT_SCAN_EXTENSIONS = {".php", ".js", ".html", ".css", ".htaccess"}


def iter_scannable_files() -> list[Path]:
    files: list[Path] = []
    for base in (REPO_ROOT, THEME_ROOT):
        for path in base.rglob("*"):
            if not path.is_file():
                continue
            if path.suffix.lower() not in TEXT_SCAN_EXTENSIONS and path.name != ".htaccess":
                continue
            rel = path.relative_to(REPO_ROOT).as_posix()
            if rel.startswith(".git/") or "/node_modules/" in rel:
                continue
            if rel.startswith("wp-theme/") and "vendor" in rel:
                continue
            files.append(path)
    return files


def test_htaccess_security_headers() -> None:
    htaccess = REPO_ROOT / ".htaccess"
    assert htaccess.is_file(), "Falta .htaccess en la raíz"
    content = htaccess.read_text(encoding="utf-8", errors="replace")
    for needle in HTACCESS_REQUIRED:
        assert needle in content, f".htaccess: falta directiva o cabecera «{needle}»"
    for folder in HTACCESS_BLOCKED_DIRS:
        assert folder in content, f".htaccess: no bloquea acceso a «{folder}/»"


def test_htaccess_denies_sensitive_extensions() -> None:
    content = (REPO_ROOT / ".htaccess").read_text(encoding="utf-8", errors="replace")
    blocks_env = ".env" in content or "|env|" in content or r"\.env" in content
    assert blocks_env, ".htaccess: no bloquea archivos .env"
    assert "Require all denied" in content or "Deny from all" in content


@pytest.mark.parametrize("php_path", sorted(THEME_ROOT.rglob("*.php")), ids=lambda p: p.relative_to(REPO_ROOT).as_posix())
def test_theme_php_abspath_guard(php_path: Path) -> None:
    text = php_path.read_text(encoding="utf-8", errors="replace")
    assert "ABSPATH" in text, f"{php_path}: sin protección ABSPATH"


def test_no_hardcoded_secrets_in_scannable_files() -> None:
    hits: list[str] = []
    for path in iter_scannable_files():
        text = path.read_text(encoding="utf-8", errors="replace")
        for pattern, label in SECRET_PATTERNS:
            if pattern.search(text):
                hits.append(f"{path.relative_to(REPO_ROOT)} ({label})")
    assert not hits, "Posibles secretos en el repositorio:\n" + "\n".join(hits)


def test_no_dangerous_php_patterns_in_theme() -> None:
    hits: list[str] = []
    for path in THEME_ROOT.rglob("*.php"):
        text = path.read_text(encoding="utf-8", errors="replace")
        for pattern, label in DANGEROUS_PHP:
            if pattern.search(text):
                hits.append(f"{path.relative_to(REPO_ROOT)}: {label}")
    assert not hits, "Patrones PHP de alto riesgo:\n" + "\n".join(hits)

"""Comprobaciones estáticas de seguridad (mínimas)."""

from __future__ import annotations

import re
from pathlib import Path

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


def test_htaccess_hardening() -> None:
    htaccess = REPO_ROOT / ".htaccess"
    assert htaccess.is_file(), "Falta .htaccess en la raíz"
    content = htaccess.read_text(encoding="utf-8", errors="replace")
    for needle in HTACCESS_REQUIRED:
        assert needle in content, f".htaccess: falta «{needle}»"
    for folder in HTACCESS_BLOCKED_DIRS:
        assert folder in content, f".htaccess: no bloquea «{folder}/»"
    blocks_env = ".env" in content or "|env|" in content or r"\.env" in content
    assert blocks_env, ".htaccess: no bloquea .env"
    assert "Require all denied" in content or "Deny from all" in content


def test_theme_php_hardening() -> None:
    missing_abspath: list[str] = []
    dangerous: list[str] = []
    for path in THEME_ROOT.rglob("*.php"):
        text = path.read_text(encoding="utf-8", errors="replace")
        if "ABSPATH" not in text:
            missing_abspath.append(path.relative_to(REPO_ROOT).as_posix())
        for pattern, label in DANGEROUS_PHP:
            if pattern.search(text):
                dangerous.append(f"{path.name}: {label}")
    assert not missing_abspath, "PHP sin ABSPATH:\n" + "\n".join(missing_abspath[:8])
    assert not dangerous, "Patrones PHP de riesgo:\n" + "\n".join(dangerous)


def test_no_hardcoded_secrets() -> None:
    hits: list[str] = []
    for path in iter_scannable_files():
        text = path.read_text(encoding="utf-8", errors="replace")
        for pattern, label in SECRET_PATTERNS:
            if pattern.search(text):
                hits.append(f"{path.relative_to(REPO_ROOT)} ({label})")
    assert not hits, "Posibles secretos:\n" + "\n".join(hits[:8])

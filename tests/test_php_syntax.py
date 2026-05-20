"""Sintaxis PHP del tema WordPress (un solo barrido)."""

from __future__ import annotations

import shutil
import subprocess

import pytest

from conftest import THEME_ROOT


def test_theme_php_syntax_batch() -> None:
    php = shutil.which("php")
    if not php:
        pytest.skip("PHP no instalado en el entorno")
    errors: list[str] = []
    for path in sorted(THEME_ROOT.rglob("*.php")):
        result = subprocess.run(
            [php, "-l", str(path)],
            capture_output=True,
            text=True,
            check=False,
        )
        if result.returncode != 0:
            errors.append(f"{path.name}: {result.stderr.strip() or result.stdout.strip()}")
    assert not errors, "Errores de sintaxis PHP:\n" + "\n".join(errors[:10])

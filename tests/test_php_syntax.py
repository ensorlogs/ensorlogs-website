"""Sintaxis PHP del tema WordPress."""

from __future__ import annotations

import shutil
import subprocess
from pathlib import Path

import pytest

from conftest import THEME_ROOT


@pytest.fixture(scope="session")
def php_binary() -> str | None:
    return shutil.which("php")


@pytest.mark.parametrize("php_path", sorted(THEME_ROOT.rglob("*.php")), ids=lambda p: p.name)
def test_php_syntax(php_path: Path, php_binary: str | None) -> None:
    if not php_binary:
        pytest.skip("PHP no instalado en el entorno")
    result = subprocess.run(
        [php_binary, "-l", str(php_path)],
        capture_output=True,
        text=True,
        check=False,
    )
    assert result.returncode == 0, result.stdout + result.stderr

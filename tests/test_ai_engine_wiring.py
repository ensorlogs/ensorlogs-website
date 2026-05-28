"""Checks Ensorlogs AI Engine plugin wiring."""

from __future__ import annotations

from pathlib import Path

from conftest import REPO_ROOT


PLUGIN_ROOT = (
    REPO_ROOT
    / "wp-theme"
    / "ensorlogs"
    / "plugins"
    / "ensorlogs-ai-engine"
)


def test_plugin_bootstrap_exists_and_loads_classes() -> None:
    php = (PLUGIN_ROOT / "ensorlogs-ai-engine.php").read_text(encoding="utf-8")
    assert "Plugin Name: Ensorlogs AI Engine" in php
    assert "class-eae-admin.php" in php
    assert "class-eae-rest.php" in php
    assert "class-eae-openai.php" in php
    assert "class-eae-prompt.php" in php


def test_admin_panel_has_required_fields() -> None:
    php = (PLUGIN_ROOT / "includes" / "class-eae-admin.php").read_text(encoding="utf-8")
    assert "¿Qué quieres hablar en este LOG?" in php
    assert "GENERAR LOG ENSORLOGS" in php
    assert "edit_form_after_title" in php
    assert "ensor_article" in php
    assert "ensorlogs_ai_openai_api_key" in php


def test_rest_endpoint_and_guardrails() -> None:
    rest_php = (PLUGIN_ROOT / "includes" / "class-eae-rest.php").read_text(encoding="utf-8")
    prompt_php = (PLUGIN_ROOT / "includes" / "class-eae-prompt.php").read_text(encoding="utf-8")
    assert "ensorlogs-ai/v1" in rest_php
    assert "/generate-log" in rest_php
    assert "wp_verify_nonce" in rest_php
    assert "sanitize_generated_html" in prompt_php
    assert 'data-aud="context"' in prompt_php
    assert 'class="ensor-quiz"' in prompt_php

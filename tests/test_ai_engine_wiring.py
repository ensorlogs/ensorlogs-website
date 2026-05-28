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


def test_admin_panel_single_metabox_and_stacks() -> None:
    php = (PLUGIN_ROOT / "includes" / "class-eae-admin.php").read_text(encoding="utf-8")
    assert "¿Qué quieres hablar en este LOG?" in php
    assert "GENERAR LOG ENSORLOGS" in php
    assert "add_meta_box" in php
    assert "after_title" in php
    bootstrap = (PLUGIN_ROOT / "ensorlogs-ai-engine.php").read_text(encoding="utf-8")
    assert "eae-inline-boot.php" in bootstrap
    assert "ensorlogs_ai_engine_panel" in php
    assert "ensor_tema" in php
    assert "eae_stack[]" in php
    assert "edit_form_after_title" not in php
    assert "admin_notices" not in php
    assert "eae-log-type" not in php
    assert "eae-level" not in php
    assert "eae-audience" not in php
    assert "remove_meta_box" in php
    assert "ensor_article_quiz" not in php.split("hide_legacy_section_metaboxes")[1].split("function")[0]


def test_rest_endpoint_stacks_and_guardrails() -> None:
    rest_php = (PLUGIN_ROOT / "includes" / "class-eae-rest.php").read_text(encoding="utf-8")
    prompt_php = (PLUGIN_ROOT / "includes" / "class-eae-prompt.php").read_text(encoding="utf-8")
    js = (PLUGIN_ROOT / "assets/js/eae-editor.js").read_text(encoding="utf-8")
    assert "ensorlogs-ai/v1" in rest_php
    assert "/generate-log" in rest_php
    assert "wp_verify_nonce" in rest_php
    assert "'stacks'" in rest_php
    assert "extract_quiz_for_meta" in rest_php
    assert "logType" not in rest_php
    assert "sanitize_generated_html" in prompt_php
    assert 'data-aud="student"' in prompt_php
    assert 'data-aud="teacher"' in prompt_php
    assert 'data-aud="professional"' in prompt_php
    assert "Público principal:" in prompt_php
    assert "Nivel técnico:" in prompt_php
    assert "Tipo de log:" in prompt_php
    assert "stacks" in js
    openai_php = (PLUGIN_ROOT / "includes" / "class-eae-openai.php").read_text(encoding="utf-8")
    assert "chat/completions" in openai_php
    assert "blockContent" in rest_php

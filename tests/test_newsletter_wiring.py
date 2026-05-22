"""Comprueba que el newsletter Mailchimp esté cableado en tema y JS."""

from __future__ import annotations

from pathlib import Path

from conftest import REPO_ROOT, THEME_ROOT


def test_newsletter_php_registers_ajax_and_put_upsert():
    php = (THEME_ROOT / "inc" / "newsletter.php").read_text(encoding="utf-8")
    assert "wp_ajax_ensor_newsletter_subscribe" in php
    assert "wp_ajax_nopriv_ensor_newsletter_subscribe" in php
    assert "members/%s" in php
    assert "'method'  => 'PUT'" in php
    assert "check_ajax_referer('ensor_newsletter_subscribe'" in php


def test_newsletter_js_posts_to_admin_ajax():
    js = (THEME_ROOT / "assets" / "js" / "ensor-newsletter.js").read_text(encoding="utf-8")
    assert "ensor-newsletter-native-form" in js
    assert "cfg.ajaxUrl" in js
    assert "ensor_newsletter_subscribe" in js
    assert "body.append('nonce'" in js
    assert "ensor-newsletter-form__feedback" in js
    assert "successMessage" in (THEME_ROOT / "functions.php").read_text(encoding="utf-8")


def test_functions_localize_newsletter_config():
    php = (THEME_ROOT / "functions.php").read_text(encoding="utf-8")
    assert "ensorNewsletter" in php
    assert "admin_url('admin-ajax.php')" in php


def test_customizer_preserves_mailchimp_api_key_on_empty_save():
    php = (THEME_ROOT / "inc" / "customizer.php").read_text(encoding="utf-8")
    assert "ensor_mailchimp_api_key" in php
    assert "get_theme_mod('ensor_mailchimp_api_key'" in php

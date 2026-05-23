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
    assert "wp_verify_nonce($nonce, 'ensor_newsletter_subscribe')" in php
    assert "ensor_newsletter_status" in php


def test_newsletter_js_posts_to_admin_ajax():
    js = (THEME_ROOT / "assets" / "js" / "ensor-newsletter.js").read_text(encoding="utf-8")
    php = (THEME_ROOT / "inc" / "newsletter.php").read_text(encoding="utf-8")
    assert "ensor-newsletter-native-form" in js
    assert "cfg.ajaxUrl" in js
    assert "ensor_newsletter_subscribe" in js
    assert "ensor_newsletter_refresh_nonce" in js
    assert "syncConfigHints" in js
    assert "hydrateCfg" in js
    assert "isMailchimpReady" in js
    assert "statusAction" in php
    assert "successMessage" in php
    assert "fetchFreshNonce" in js
    assert "body.append('nonce'" in js
    assert "ensor-newsletter-form__feedback" in js


def test_functions_localize_newsletter_config():
    php = (THEME_ROOT / "functions.php").read_text(encoding="utf-8")
    newsletter = (THEME_ROOT / "inc" / "newsletter.php").read_text(encoding="utf-8")
    assert "ensorNewsletter" in php
    assert "ensorlogs_newsletter_client_config" in php
    assert "data-ensor-newsletter" in newsletter
    assert "'configured'" in newsletter


def test_ensor_theme_js_block_comments_have_no_backticks():
    """Backticks en comentarios rompen el bundle JS combinado de SiteGround (tras tw-elements)."""
    js_dir = THEME_ROOT / "assets" / "js"
    offenders: list[str] = []
    for path in sorted(js_dir.glob("ensor-*.js")):
        header, _, _ = path.read_text(encoding="utf-8").partition("*/")
        if "`" in header:
            offenders.append(path.name)
    assert not offenders, "Quita backticks del comentario inicial en: " + ", ".join(offenders)


def test_newsletter_standalone_script_in_footer():
    php = (THEME_ROOT / "inc" / "newsletter.php").read_text(encoding="utf-8")
    assert "ensorlogs_print_newsletter_script_standalone" in php
    assert "data-cfasync=\"false\"" in php
    assert "ensor-newsletter.js" in php


def test_customizer_preserves_mailchimp_api_key_on_empty_save():
    php = (THEME_ROOT / "inc" / "customizer.php").read_text(encoding="utf-8")
    assert "ensor_mailchimp_api_key" in php
    assert "get_theme_mod('ensor_mailchimp_api_key'" in php

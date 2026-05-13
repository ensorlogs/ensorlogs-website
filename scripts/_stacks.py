"""Catálogo único de stacks usado por scripts de renderizado.

Mantiene paridad con `wp-theme/ensorlogs/inc/stacks.php`. Si añades un stack
aquí, añádelo también en el theme para que el badge salga igual en ambos
lados.
"""
from __future__ import annotations


def _svg(path: str, viewbox: str = "0 0 24 24") -> str:
    return (
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="{viewbox}" '
        'fill="none" stroke="currentColor" stroke-width="1.6" '
        'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        f'{path}</svg>'
    )


STACKS = {
    "wordpress": {
        "label": "WordPress",
        "color": "#21759b",
        "icon": _svg(
            '<circle cx="12" cy="12" r="9"/>'
            '<path d="M3.2 9.5h7.6M5.5 14.5l3.5-9M14.7 14.5l3.5-9M16 6.5l-2 8M9 6.5l2 8"/>'
        ),
    },
    "linux": {
        "label": "Linux",
        "color": "#3b3b3b",
        "icon": _svg(
            '<path d="M12 3c-2.2 0-3.5 1.7-3.5 4.5 0 1.8.5 3.3 1.4 4.7-1.5 1.2-3.2 3.4-3.4 5.6-.1 1 .3 1.7 1 2 1 .4 2.4-.5 3.4-1.5.5-.5 1-.5 1.6-.5s1 .1 1.6.5c.9 1 2.3 1.9 3.4 1.5.7-.3 1.1-1 1-2-.2-2.2-1.9-4.4-3.4-5.6.9-1.4 1.4-2.9 1.4-4.7C15.5 4.7 14.2 3 12 3z"/>'
            '<circle cx="10.5" cy="8" r=".7" fill="currentColor"/>'
            '<circle cx="13.5" cy="8" r=".7" fill="currentColor"/>'
        ),
    },
    "ia": {
        "label": "IA",
        "color": "#7c3aed",
        "icon": _svg(
            '<rect x="5" y="6" width="14" height="12" rx="3"/>'
            '<circle cx="9.5" cy="12" r="1.1" fill="currentColor"/>'
            '<circle cx="14.5" cy="12" r="1.1" fill="currentColor"/>'
            '<path d="M12 3v3M8 21v-3M16 21v-3"/>'
        ),
    },
    "database": {
        "label": "Database",
        "color": "#0ea5e9",
        "icon": _svg(
            '<ellipse cx="12" cy="6" rx="7" ry="3"/>'
            '<path d="M5 6v6c0 1.7 3.1 3 7 3s7-1.3 7-3V6M5 12v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/>'
        ),
    },
    "crm": {
        "label": "CRM",
        "color": "#e11d48",
        "icon": _svg(
            '<circle cx="9" cy="9" r="3"/>'
            '<path d="M3 20c.6-3 3-5 6-5s5.4 2 6 5M15 11h6M15 15h4"/>'
        ),
    },
    "marketing": {
        "label": "Marketing",
        "color": "#f97316",
        "icon": _svg(
            '<path d="M3 11l14-5v12L3 13zM3 11v2M7 12.4v4.6c0 1 .8 2 2 2h.5c1 0 1.5-1 1.5-2v-4"/>'
        ),
    },
    "python": {
        "label": "Python",
        "color": "#facc15",
        "icon": _svg(
            '<path d="M9 4h6a3 3 0 0 1 3 3v3H9V8H6a3 3 0 0 0-3 3v3a3 3 0 0 0 3 3h3v-2"/>'
            '<path d="M15 20H9a3 3 0 0 1-3-3v-3h9v2h3a3 3 0 0 0 3-3v-3a3 3 0 0 0-3-3h-3v2"/>'
            '<circle cx="9" cy="6.5" r=".6" fill="currentColor"/>'
            '<circle cx="15" cy="17.5" r=".6" fill="currentColor"/>'
        ),
    },
    "google": {
        "label": "Google",
        "color": "#4285f4",
        "icon": _svg(
            '<path d="M21 12.2c0-.7-.1-1.3-.2-1.9H12v3.7h5.1c-.2 1.2-.9 2.2-2 2.9v2.4h3.2c1.9-1.7 3-4.3 3-7.1z"/>'
            '<path d="M12 21c2.7 0 5-.9 6.6-2.4l-3.2-2.5c-.9.6-2 1-3.4 1-2.6 0-4.9-1.8-5.7-4.2H3.1v2.6C4.7 18.7 8 21 12 21z"/>'
            '<path d="M6.3 12.8c-.2-.6-.3-1.2-.3-1.8s.1-1.2.3-1.8V6.6H3.1A9 9 0 0 0 2 11c0 1.5.4 2.9 1.1 4.1l3.2-2.3z"/>'
            '<path d="M12 6.6c1.5 0 2.8.5 3.8 1.5l2.8-2.8C17 3.9 14.7 3 12 3 8 3 4.7 5.3 3.1 8.4l3.2 2.6C7.1 8.4 9.4 6.6 12 6.6z"/>'
        ),
    },
    "servidores": {
        "label": "Servidores",
        "color": "#10b981",
        "icon": _svg(
            '<rect x="4" y="4" width="16" height="6" rx="2"/>'
            '<rect x="4" y="14" width="16" height="6" rx="2"/>'
            '<circle cx="8" cy="7" r=".7" fill="currentColor"/>'
            '<circle cx="8" cy="17" r=".7" fill="currentColor"/>'
            '<path d="M14 7h3M14 17h3"/>'
        ),
    },
    "it": {
        "label": "IT",
        "color": "#6366f1",
        "icon": _svg(
            '<rect x="3" y="4" width="18" height="12" rx="2"/>'
            '<path d="M9 20h6M12 16v4"/>'
        ),
    },
    "windows": {
        "label": "Windows",
        "color": "#0078d4",
        "icon": _svg(
            '<path d="M3 7l8-1v6H3zM11 6l10-1.5V12H11zM3 13h8v5l-8-1zM11 13h10v6.5L11 18z"/>'
        ),
    },
    "mac": {
        "label": "Mac",
        "color": "#a3a3a3",
        "icon": _svg(
            '<path d="M16.6 13.2c0-2 1.7-2.9 1.8-3-1-1.4-2.5-1.6-3-1.6-1.3-.1-2.6.7-3.2.7-.6 0-1.7-.7-2.8-.7-1.5 0-2.8.9-3.6 2.2-1.5 2.7-.4 6.7 1.1 8.9.7 1.1 1.5 2.3 2.7 2.2 1.1 0 1.5-.7 2.8-.7s1.7.7 2.8.7c1.2 0 1.9-1.1 2.6-2.2.8-1.2 1.1-2.5 1.2-2.6-.1 0-2.4-.9-2.4-3.9zM13.6 6.9c.6-.7 1-1.7.9-2.7-.9 0-1.9.6-2.6 1.3-.5.6-1 1.6-.9 2.6 1 0 1.9-.5 2.6-1.2z"/>'
        ),
    },
}


def stack_label(slug: str) -> str:
    s = (slug or "").lower().strip()
    return STACKS.get(s, {}).get("label", s.capitalize() if s else "")


def stack_color(slug: str) -> str:
    s = (slug or "").lower().strip()
    return STACKS.get(s, {}).get("color", "")


def stack_icon(slug: str) -> str:
    s = (slug or "").lower().strip()
    return STACKS.get(s, {}).get("icon", "")


def stack_badge_html(slug: str, base_href: str = "blog.html?tema=", extra_class: str = "") -> str:
    """Devuelve un <a class="ensor-reader-stack"> con icono + label."""
    s = (slug or "").lower().strip()
    label = stack_label(s) or s
    icon = stack_icon(s)
    color = stack_color(s)
    href = f"{base_href}{s}"
    cls = "ensor-reader-stack" + (f" {extra_class}" if extra_class else "")
    style = f' style="--ensor-stack-color:{color}"' if color else ""
    parts = [f'<a href="{href}" class="{cls}"{style} rel="tag">']
    if icon:
        parts.append(f'<span class="ensor-reader-stack__icon" aria-hidden="true">{icon}</span>')
    parts.append(f'<span class="ensor-reader-stack__label">{label}</span>')
    parts.append("</a>")
    return "".join(parts)

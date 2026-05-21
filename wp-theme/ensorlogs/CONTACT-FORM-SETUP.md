# Página Contacto (WordPress)

La página **Contacto** ya no incluye formulario de mensajes. Muestra correo, redes, Calendly y **suscripción a la lista** (botón que abre el modal de Mailchimp).

El código del antiguo formulario (`inc/contact-form.php`) sigue en el tema por si lo reactivas; no se usa en la plantilla actual.

---

# Formulario de contacto por correo (desactivado en plantilla)

Si vuelves a activar el formulario, usa [Cloudflare Turnstile](https://www.cloudflare.com/products/turnstile/) integrado en el código.

## 1. Crear Turnstile (gratis)

1. Entra en [Cloudflare Dashboard](https://dash.cloudflare.com/) → **Turnstile** → **Add site**.
2. Nombre: `ensorlogs.com`.
3. Dominios: `ensorlogs.com` y, si pruebas en local, `localhost`.
4. Modo: **Managed** (recomendado).
5. Copia **Site Key** y **Secret Key**.

## 2. Configurar el tema en WordPress

1. **Apariencia → Personalizar → Ensorlogs → Formulario de contacto**.
2. Pega **Site Key** y **Secret Key**.
3. Publica los cambios.
4. Abre `/contact/` y comprueba que aparece el widget Turnstile (no la suma 5+6).
5. Envía un mensaje de prueba.

Si eres administrador y faltan claves, verás un aviso amarillo en el escritorio de WordPress.

## 3. Correo con turboSMTP (recomendado en Ensorlogs)

El formulario del tema llama a `wp_mail()`. Con **turboSMTP** debes enlazar WordPress al SMTP con un plugin (el tema no guarda las claves SMTP).

### Verificar el dominio en DNS (SPF + DKIM + DMARC)

En turboSMTP → **Verify your domain** creas **tres** registros TXT en el DNS de **ensorlogs.com** (SiteGround, Cloudflare, etc.). Sin esto, los correos pueden ir a spam o rechazarse.

Resumen rápido:

| # | Host (nombre) | Tipo | Valor |
|---|---------------|------|--------|
| 1 | `@` | TXT | `v=spf1 a mx include:spf.turbo-smtp.com ?all` |
| 2 | `turbo-smtp._domainkey` | TXT | `k=rsa; p=...` (cadena larga del panel) |
| 3 | `_dmarc` | TXT | `v=DMARC1; p=none;` |

**Registro 1 — SPF** (un solo TXT en la raíz del dominio):

| Campo DNS | Valor |
|-----------|--------|
| Tipo | `TXT` |
| Nombre / Host | `@` (o vacío = raíz `ensorlogs.com`) |
| Valor | `v=spf1 a mx include:spf.turbo-smtp.com ?all` |

Si ya tenías un SPF, no crees otro: edita el existente y añade `include:spf.turbo-smtp.com` justo después de `v=spf1` (como indica turboSMTP).

**Registro 2 — DKIM**:

| Campo DNS | Valor |
|-----------|--------|
| Tipo | `TXT` |
| Nombre / Host | `turbo-smtp._domainkey` |
| Valor | La cadena completa que te da turboSMTP (empieza por `k=rsa; p=...`) |

Ejemplo del valor DKIM (usa el de **tu panel**, puede coincidir con este):

```text
k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDT3MWLni6so1q9eQggRYBCLHFjohZkCnYHH8gZNDBm6zRrodRVpWpJQW7x3cWWiuBhS1X0IfBB80l5tqFa+yc+mVgnk8tkUzOHFbPQPp4fi7egTpMtsQW/ZMrxw73SItNvPr72qvJTYZNPxarMx+ULjEWybcfEdXHPY8jslGcpCwIDAQAB
```

En algunos paneles el host se escribe como `turbo-smtp._domainkey.ensorlogs.com`; en otros solo `turbo-smtp._domainkey`. Si falla la verificación, prueba la variante que use tu proveedor DNS.

**Registro 3 — DMARC**:

| Campo DNS | Valor |
|-----------|--------|
| Tipo | `TXT` |
| Nombre / Host | `_dmarc` |
| Valor | `v=DMARC1; p=none;` |

`p=none` es el modo de monitorización que turboSMTP suele proponer al empezar (no bloquea correos; solo informa). Si más adelante turboSMTP te sugiere otro valor, sustitúyelo.

**Después de guardar los 3 TXT:** espera 15–60 minutos (a veces hasta 24 h), vuelve a turboSMTP y pulsa verificar el dominio. Luego el test de WP Mail SMTP y el formulario de contacto.

### Crear las claves API/SMTP en turboSMTP

1. [turboSMTP](https://www.serversmtp.com/) → inicia sesión → **API / SMTP** (o **Create API Key**).
2. Genera un par **Consumer Key** + **Consumer Secret** (son usuario y contraseña SMTP).

### Configurar WordPress (WP Mail SMTP u otro)

Instala **WP Mail SMTP** (el más usado) o el plugin oficial de turboSMTP si lo prefieres.

Con **WP Mail SMTP → Other SMTP**, usa exactamente lo que muestra turboSMTP en «Connecting via SMTP»:

| Campo | Valor |
|--------|--------|
| SMTP Host | `pro.turbo-smtp.com` |
| Encryption | **TLS** (recomendado) o SSL |
| SMTP Port | **587** (TLS) o **465** (SSL) |
| Authentication | Sí |
| SMTP Username | Tu **Consumer Key** |
| SMTP Password | Tu **Consumer Secret** |
| From Email | `hello@ensorlogs.com` (correo verificado en turboSMTP) |
| From Name | `Ensorlogs` |
| Force From Email | Activado (recomendado) |

Puertos alternativos según turboSMTP: sin SSL `25`, `587`, `2525`; con SSL `465`, `25025`.

### Comprobar antes del formulario

1. En el plugin SMTP → **Send Test Email** → debe llegar a tu bandeja.
2. Si el test falla, el formulario de contacto tampoco enviará (aunque Turnstile pase).

### Errores frecuentes

- **Usuario/contraseña**: en turboSMTP no es el login de la web; es **Consumer Key** / **Consumer Secret**.
- **From no verificado**: el remitente `hello@ensorlogs.com` debe estar autorizado en turboSMTP/DKIM del dominio.
- **Puerto bloqueado**: prueba 587 + TLS; si falla, 465 + SSL.

Sin SMTP configurado, el formulario puede mostrar «Mensaje enviado» y aun así no llegar el correo (depende del hosting).

## 4. Página de contacto

Debe existir una página con slug **`contact`** y plantilla del tema (seed del tema la crea).

El formulario envía a `admin-post.php` con acción `ensorlogs_contact` y reenvía a `/contact/#contact-form` con mensaje de éxito o error.

## Sitio estático (HTML)

Si publicas solo HTML + `contact-form.php`:

1. Copia `contact-secrets.example.php` → `contact-secrets.php` (no subir a Git).
2. Pega las claves en ese archivo.
3. En `contact.html`, pon la Site Key en `data-sitekey=""` del div `#ensor-contact-turnstile`.

En WordPress en producción no hace falta `contact-secrets.php`; todo va por el Personalizador.

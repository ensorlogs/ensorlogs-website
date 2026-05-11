#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Generador de artículos del blog (HTML estático) — Ensorlogs Web
==============================================================

¿Qué problema resuelve?
    Evitar copiar y pegar a mano 6+ páginas casi idénticas (mismo header, nav, footer).
    Cada artículo comparte la “carcasa” de ``blogs-details.html`` y solo cambia el
    bloque central (metas, H1, cuerpo, tags, “relacionados”).

¿Cómo funciona a grandes rasgos?
    1. Se lee ``blogs-details.html`` como un string gigante.
    2. Se corta en tres trozos: ``head`` (hasta </head>), ``header`` (body hasta main),
       ``footer`` (desde el comentario de fin de main hasta el final).
    3. La función ``page()`` ensambla esos trozos + datos del diccionario ``ARTICLES``.
    4. Se escribe un archivo ``articulo-....html`` por entrada.

Cómo ejecutarlo (siempre desde la RAÍZ del repo, no desde ``scripts/``)::

    python3 scripts/render_blog_articles.py

Advertencia pedagógica / práctica:
    Si editas un ``articulo-*.html`` a mano y vuelves a correr este script,
    **perderás** esos cambios. La “fuente de verdad” hoy es este archivo + la plantilla.
    Para un flujo tipo CMS, el siguiente paso sería Markdown + front matter.

Ver también: ``docs/GUIA-ESTUDIANTES.md`` y ``docs/ARQUITECTURA.md``.
"""
from pathlib import Path

# Raíz del repositorio (padre de la carpeta ``scripts/``)
ROOT = Path(__file__).resolve().parents[1]
SRC = ROOT / "blogs-details.html"
OUT = ROOT

text = SRC.read_text(encoding="utf-8")
body_start = text.index("<body")
head = text[: text.index("</head>") + len("</head>")]
footer = text[text.index("<!--~~./ end Main Content") :]
header = text[body_start : text.index('<div class="main-content mt-28')]


def meta_block(title, desc, keywords, canonical_path, og_title=None):
    """Devuelve el bloque de <meta> + <title> sustituyendo el bloque genérico de la plantilla."""
    og_title = og_title or title
    return f"""    <meta name="keywords"
        content="{keywords}">
    <meta name="description"
        content="{desc}">
    <meta name="author" content="Ensor Sánchez · Ensorlogs">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://ensorlogs.com/{canonical_path}">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="Ensorlogs">
    <meta property="og:locale" content="es_VE">
    <meta property="og:title" content="{og_title}">
    <meta property="og:description" content="{desc}">
    <meta property="og:url" content="https://ensorlogs.com/{canonical_path}">

    <!-- Site Title
    ================================================== -->
    <title>{title} | Ensorlogs</title>"""


RELATED_CARD = """
                            <div class="blog-item group">
                                <div class="thumbnail relative overflow-hidden rounded-xl">
                                    <a href="{href}">
                                        <img src="{img}" alt="{alt}" class="w-full transition-all duration-300 group-hover:scale-110" width="800" height="450" decoding="async">
                                    </a>
                                    <div class="tags absolute right-4 top-4">
                                        <a href="blog.html?tema={tema}" class="bg-white/60 dark:bg-black/50 transition-all px-3 py-1 rounded-3xl text-darkGray dark:text-pastelGrey text-sm">{tag}</a>
                                    </div>
                                </div>
                                <div class="description py-6 px-4 pb-4">
                                    <h4 class="text-xl 2xl:text-2xl font-bold text-darkGray dark:text-pastelGrey mb-1">
                                        <a href="{href}" class="inline bg-gradient-to-r from-current from-0% to-current to-100% bg-no-repeat bg-[length:0_1px] bg-[0_95%] ease-in-out duration-200 transition-[background-size] hover:bg-[length:100%_1px]">{title}</a>
                                    </h4>
                                    <p class="line-clamp-2 !leading-normal text-sm mt-2">{excerpt}</p>
                                    <p class="mt-4"><a href="{href}" class="font-semibold text-base text-darkGray dark:text-pastelGrey">Seguir leyendo →</a></p>
                                </div>
                            </div>"""


def comments_short():
    return """
                    <div class="comments">
                        <h3 class="text-xl lg:text-2xl text-darkGray dark:text-pastelGrey font-bold mb-4">
                            Comentarios
                        </h3>
                        <p class="text-regular text-darkGray dark:text-pastelGrey max-w-2xl">
                            ¿Te resuena algo de lo que cuento desde Caracas? Cuando active el formulario público, podrás dejar dudas o experiencias. Mientras tanto, puedes escribirme por <a href="contact.html" class="underline font-medium text-powerBlack dark:text-pastelGrey">contacto</a>.
                        </p>
                    </div>"""


def page(article):
    """Construye y escribe un HTML completo para un elemento de ``ARTICLES``."""
    mh = head
    i0 = mh.index('<meta name="keywords"')
    i1 = mh.index("<!-- Site Favicon", i0)
    mh = mh[:i0] + meta_block(**article["meta"]) + "\n\n    " + mh[i1:]
    hero = article["hero"]
    body = f"""
    <div class="main-content mt-28 md:mt-32 lg:mt-36 xl:mt-48">
        <div class="container space-y-6">
           <div class="blog-details-wrapper" data-aos="fade-up">
                <div class="details-header max-w-[870px] mx-auto">
                    <h1 class="font-bold text-3xl lg:text-4xl text-darkGray dark:text-pastelGrey mb-2">
                        {article["h1"]}
                    </h1>
                    <ul class="meta flex flex-wrap items-center gap-3 sm:gap-4 lg:gap-6 mb-6 text-sm lg:text-base">
                        <li>
                            <a href="blog.html?tema={article["primary_tema"]}" class="bg-white dark:bg-black/50 transition-all inline-flex px-6 py-2 rounded-3xl text-darkGray dark:text-pastelGrey">
                                {article["pill"]}
                            </a>
                        </li>
                        <li>
                            <i class="fal fa-clock" aria-hidden="true"></i>
                            {article["date"]}
                        </li>
                        <li>
                            <i class="fal fa-map-marker-alt" aria-hidden="true"></i>
                            Caracas, Venezuela
                        </li>
                        <li>
                            <i class="fal fa-user" aria-hidden="true"></i>
                            Ensor Sánchez · ENSOR.LOGS
                        </li>
                    </ul>
                </div>
                <div class="thumbnail mb-12 max-w-screen-lg mx-auto">
                    <img src="{hero["src"]}" alt="{hero["alt"]}" width="1600" height="900" decoding="async" class="rounded-xl xl:rounded-2xl max-md:h-60 w-full object-cover object-center">
                </div>
                <div class="*:max-w-[870px] *:mx-auto space-y-12">
                    <div class="details-body text-darkGray dark:text-pastelGrey leading-relaxed space-y-6">
                        {article["html"]}
                    </div>
                    <div class="meta-info grid md:grid-cols-2 gap-4">
                        <div class="tags flex flex-wrap items-center gap-4 text-sm">
                            <span class="font-semibold text-powerBlack dark:text-pastelGrey">Temas</span>
                            {article["tag_links"]}
                        </div>
                        <div class="share flex flex-wrap items-center md:justify-end gap-4 text-sm text-darkGray dark:text-pastelGrey">
                            <a href="blog.html" class="underline font-medium">Volver al blog</a>
                            <a href="contact.html" class="underline font-medium">Hablemos</a>
                        </div>
                    </div>
                    {comments_short()}
                    <div class="recent-posts">
                        <h3 class="text-xl lg:text-2xl text-darkGray dark:text-pastelGrey font-bold mb-4">
                            Otros artículos
                        </h3>
                        <div class="grid sm:grid-cols-2 gap-6 *:p-2 *:rounded-xl xl:*:rounded-3xl *:bg-gradient-to-b *:from-milkWhite *:to-seashell *:dark:from-metalBlack *:dark:to-oilBlack *:border *:border-gray-100 dark:*:border-white/5">
                            {article["related_html"]}
                        </div>
                    </div>
                </div>
           </div>
        </div>
    </div>
"""
    out = mh + "\n" + header + body + footer
    (OUT / article["filename"]).write_text(out, encoding="utf-8")


def tag_links(slugs_labels):
    parts = []
    for slug, label in slugs_labels:
        parts.append(
            f'<a href="blog.html?tema={slug}" class="inline-flex transition-all duration-200 border border-black/5 dark:border-white/5 hover:border-white hover:bg-white dark:hover:bg-black dark:hover:text-pastelGrey px-4 py-2 rounded-3xl text-sm">{label}</a>'
        )
    return "\n                            ".join(parts)


ARTICLES = [
    {
        "filename": "articulo-linux-terminal-ia-caracas-2026.html",
        "primary_tema": "linux",
        "pill": "Linux",
        "h1": "Estudiar Linux en Caracas en 2026: terminal, WSL y el nuevo compañero llamado IA",
        "date": "Enero 2026",
        "meta": {
            "title": "Linux en Caracas 2026: terminal, WSL e IA para estudiantes IT",
            "desc": "Guía práctica para estudiar Linux desde Venezuela en 2026: WSL, flujo de terminal, productividad con IA y qué priorizar si buscas trabajo remoto o enseñar IT en Caracas.",
            "keywords": "Linux Caracas, estudiar Linux Venezuela 2026, WSL, terminal bash, IT Caracas, trabajo remoto Venezuela, cursores IA desarrollo",
            "canonical_path": "articulo-linux-terminal-ia-caracas-2026.html",
        },
        "hero": {
            "src": "https://images.unsplash.com/photo-1629654298833-01dc4cda8337?auto=format&fit=crop&w=1600&q=82",
            "alt": "Pantalla con código y terminal — artículo Linux y estudio IT en Caracas",
        },
        "tag_links": tag_links(
            [
                ("linux", "Linux"),
                ("ia", "IA"),
                ("it", "IT"),
                ("servidores", "Servidores"),
            ]
        ),
        "html": """
                        <p class="text-lg font-medium text-powerBlack dark:text-pastelGrey border-l-4 border-[var(--ensor-accent)] pl-4">
                            Escribo esto como quien ha mezclado años de curiosidad técnica con la realidad de armar proyectos reales: si estás en Caracas (o en cualquier ciudad de Venezuela) y quieres que Linux deje de ser un “monstruo negro” para convertirse en tu herramienta diaria, este texto es para ti.
                        </p>
                        <h2 class="text-2xl font-bold text-powerBlack dark:text-pastelGrey pt-4">Por qué Linux sigue importando en 2026</h2>
                        <p>
                            En 2026 el ecosistema no dejó de moverse: más contenedores, más automatización, más pipelines que terminan en servidores Linux aunque en tu laptop uses Windows o macOS. En la práctica, si quieres trabajar con infraestructura, backend serio o DevOps, <strong>la terminal deja de ser opcional</strong>. Y aquí viene el primer choque cultural que veo en estudiantes de Caracas: en la universidad a veces todavía se enseña “el concepto” del sistema operativo, pero en la vida laboral remota te piden que sepas moverte en SSH, permisos, logs y servicios como si fuera segunda piel.
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">WSL y el laboratorio en casa (sin excusas de hardware)</h3>
                        <p>
                            No todo el mundo tiene dos máquinas o un rack en el salón. Una ruta honesta —y muy usada— es <strong>Windows Subsystem for Linux (WSL)</strong>: te permite practicar Bash, paquetes y servicios sin reinstalar tu PC de la UCV o de la oficina. Lo importante no es “fanatismo” por un sistema: es ganar <strong>horas de repetición</strong> con comandos, rutas, variables de entorno y errores reales. Eso es lo que te prepara cuando un cliente te pide revisar un VPS barato en la nube a las 10:00 p.m.
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">La IA como copiloto (no como atajo para no entender)</h3>
                        <p>
                            En 2026 cualquiera puede pedirle a un modelo que “le explique” un comando. Eso está bien para desbloquear, pero si tu meta es <strong>trabajar remoto</strong> o construir un perfil docente creíble, necesitas poder explicar tú mismo qué hiciste: por qué ese <code class="bg-black/5 dark:bg-white/10 px-1 rounded">chmod</code>, por qué ese servicio falló, cómo leer un log. Yo uso IA para acelerar búsquedas y borradores, pero la regla que me impongo es: <strong>si no puedo explicarlo en voz alta, no lo subo al servidor</strong>.
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">Stack “estudiante” vs stack “producción”</h3>
                        <p>
                            En clase a veces aprendes una distribución “de laboratorio” y una versión idealizada de redes. En trabajo real, el stack mezcla: Linux + servicios + bases de datos + automatización + monitoreo mínimo. Mi recomendación para quien arranca desde Caracas es armar un mini-proyecto: un blog estático, un API pequeño, un cron que mande alertas… lo que sea, pero que pase por <strong>SSH + Git + despliegue</strong>. Eso te da historias para entrevistas y para futuros cursos: no “cursé Linux”, sino “monté X y resolví Y”.
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">SEO local (sí, Caracas cuenta)</h3>
                        <p>
                            Si quieres que te encuentren, escribe con intención: “Caracas”, “Venezuela”, “remoto”, “estudiantes IT”, “Linux práctico”. No es relleno: es cómo la gente busca cuando quiere referencias reales, no manuales traducidos sin contexto. ENSOR.LOGS nace también como <strong>base pública de cómo pienso y enseño</strong>: servicios, CV y eventualmente talleres presenciales o híbridos en la capital.
                        </p>
                        <div class="p-6 lg:p-8 rounded-xl xl:rounded-2xl bg-gradient-to-b from-milkWhite to-seashell dark:from-metalBlack dark:to-oilBlack border border-flasWhite dark:border-flasBlack">
                            <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey mb-2">Conclusión</h3>
                            <p class="mb-0">
                                Linux en 2026 no es “lo difícil del pasado”: es la mesa donde se cocina buena parte del software serio. Practica terminal, entiende permisos, aprende a leer logs y usa la IA para ir más rápido —pero sin sustituir tu criterio. Si quieres que acompañemos un plan personalizado (laboratorio + portfolio), me escribes por <a href="contact.html" class="underline font-semibold">contacto</a>.
                            </p>
                        </div>
""",
    },
    {
        "filename": "articulo-wordpress-bloques-seguridad-caracas-2026.html",
        "primary_tema": "wordpress",
        "pill": "WordPress",
        "h1": "WordPress en 2026: bloques, seguridad y cómo enseño el stack sin marear al estudiante",
        "date": "Enero 2026",
        "meta": {
            "title": "WordPress 2026 Caracas: bloques, seguridad y enseñanza práctica",
            "desc": "Reflexión técnica y pedagógica sobre WordPress en 2026: editor de bloques, seguridad básica, rendimiento y cómo acercarlo a estudiantes y pymes en Caracas.",
            "keywords": "WordPress Caracas, Gutenberg 2026, seguridad WordPress Venezuela, enseñanza WordPress, sitios web Venezuela, SEO Caracas",
            "canonical_path": "articulo-wordpress-bloques-seguridad-caracas-2026.html",
        },
        "hero": {
            "src": "https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1600&q=82",
            "alt": "Dashboard y métricas web — WordPress y negocios digitales en Venezuela",
        },
        "tag_links": tag_links(
            [
                ("wordpress", "WordPress"),
                ("marketing", "Marketing"),
                ("google", "Google"),
                ("it", "IT"),
            ]
        ),
        "html": """
                        <p class="text-lg font-medium text-powerBlack dark:text-pastelGrey border-l-4 border-[var(--ensor-accent)] pl-4">
                            WordPress sigue siendo el “caballo de batalla” de miles de sitios en Venezuela: desde tiendas pequeñas hasta blogs institucionales. Aquí te cuento cómo lo veo en 2026 si mezclas rol docente, consultoría y la presión real de mantener sitios vivos.
                        </p>
                        <h2 class="text-2xl font-bold text-powerBlack dark:text-pastelGrey pt-4">Bloques: menos miedo, más diseño de contenido</h2>
                        <p>
                            El editor de bloques ya no es novedad, pero en aula todavía aparece la fricción: “antes era más fácil”. Mi enfoque es mostrar que <strong>los bloques son componentes</strong>: si entiendes patrones (grupos, columnas, patrones reutilizables), dejas de pelear con el mouse. Para un estudiante en Caracas que quiere freelance rápido, eso se traduce en entregar páginas más limpias y mantenibles —y eso se cobra mejor.
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">Seguridad sin dramatizar (pero sin fingir)</h3>
                        <p>
                            Actualizaciones, roles de usuario, contraseñas fuertes, backups y límites de intentos de login no son “opcionales” cuando tu sitio es vitrina de negocio. En 2026 también hay más automatización de ataques; por eso enseño una regla simple: <strong>superficie mínima</strong>. Menos plugins duplicados, menos usuarios administradores, menos “pruebas” en producción.
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">Rendimiento = SEO + credibilidad</h3>
                        <p>
                            En Venezuela muchos usuarios navegan con datos móviles; si tu web WordPress tarda, pierdes confianza antes del primer scroll. Hablo de imágenes pesadas, consultas innecesarias y hosting mal elegido. Conecto esto con <strong>Google</strong> y buenas prácticas de Core Web Vitals: no es magia, es ingeniería aplicada con criterio de negocio.
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">IA en contenidos: ética y fact-check</h3>
                        <p>
                            La IA puede ayudarte a esbozar textos, pero si vas a posicionar en Caracas y competir por intención de búsqueda local, necesitas voz propia, datos reales y fuentes. Yo uso IA para iterar titulares y esquemas, pero la versión final la escribo con mis ejemplos y mis límites —como este artículo.
                        </p>
                        <div class="p-6 lg:p-8 rounded-xl xl:rounded-2xl bg-gradient-to-b from-milkWhite to-seashell dark:from-metalBlack dark:to-oilBlack border border-flasWhite dark:border-flasBlack">
                            <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey mb-2">Conclusión</h3>
                            <p class="mb-0">
                                WordPress sigue siendo una puerta excelente al mercado: rápido de entregar, entendible para clientes, extensible. Si quieres aprender con enfoque profesional (y pensando en futuros cursos en Caracas), piensa en bloques + seguridad + performance como tríada inseparable.
                            </p>
                        </div>
""",
    },
    {
        "filename": "articulo-python-ia-estudio-trabajo-2026-venezuela.html",
        "primary_tema": "python",
        "pill": "Python",
        "h1": "Python + IA en 2026: qué estudiar de verdad si vives entre la U, el apagón y las ganas de trabajar remoto",
        "date": "Febrero 2026",
        "meta": {
            "title": "Python e IA 2026 Venezuela: guía de estudio y trabajo remoto",
            "desc": "Cómo estudiar Python en 2026 con herramientas de IA sin perder fundamentos: rutas de aprendizaje, proyectos portafolio y perspectiva desde Caracas y Venezuela.",
            "keywords": "Python Venezuela 2026, estudiar Python Caracas, IA para programadores, trabajo remoto Python, portafolio desarrollador Venezuela",
            "canonical_path": "articulo-python-ia-estudio-trabajo-2026-venezuela.html",
        },
        "hero": {
            "src": "https://images.unsplash.com/photo-1526379095098-d400fd11bf52?auto=format&fit=crop&w=1600&q=82",
            "alt": "Código Python en pantalla — aprendizaje e IA en Venezuela",
        },
        "tag_links": tag_links(
            [
                ("python", "Python"),
                ("ia", "IA"),
                ("it", "IT"),
                ("database", "Database"),
            ]
        ),
        "html": """
                        <p class="text-lg font-medium text-powerBlack dark:text-pastelGrey border-l-4 border-[var(--ensor-accent)] pl-4">
                            Si estás en Caracas, probablemente tu semana mezcla clases, colas, trabajo informal o formal, y la aspiración de “pegar remoto”. Python sigue siendo uno de los lenguajes más pedidos; la IA cambió la velocidad, no la necesidad de bases.
                        </p>
                        <h2 class="text-2xl font-bold text-powerBlack dark:text-pastelGrey pt-4">Lo que sí necesitas memorizar (aunque exista ChatGPT)</h2>
                        <p>
                            Sintaxis básica, estructuras de datos, manejo de errores, entornos virtuales y lectura de documentación. Suena aburrido, pero es lo que te salva en una entrevista técnica cuando te cortan internet o cuando el copiloto alucina. La IA en 2026 es un <strong>multiplicador</strong>, no un reemplazo de fundamentos.
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">Proyectos que cuentan historia (no 50 tutoriales clonados)</h3>
                        <p>
                            Prefiero un solo proyecto medianamente útil: scraper ético con límites, automatización de reportes, integración con una API, pequeño ETL. Para quien busca trabajo remoto desde Venezuela, eso demuestra que entiendes <strong>valor</strong>, no solo sintaxis.
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">Del aula al ticket real</h3>
                        <p>
                            En la universidad a veces se evalúa con ejercicios aislados; en trabajo aparecen requisitos incompletos, datos sucios y plazos. Mi consejo de “profesor con callos” es practicar <strong>lectura de tickets</strong>: traducir un pedido vago a tareas, estimar, preguntar. Eso lo entrenas incluso solo, escribiendo issues para ti mismo en GitHub/GitLab.
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">SEO y narrativa profesional</h3>
                        <p>
                            Si publicas en un blog como ENSOR.LOGS, piensa en intención: “Python Caracas”, “automatización Venezuela”, “IA para desarrolladores hispanohablantes”. Google premia utilidad y claridad; tu lector en Caracas premia honestidad sobre limitaciones (luz, ancho de banda, hardware).
                        </p>
                        <div class="p-6 lg:p-8 rounded-xl xl:rounded-2xl bg-gradient-to-b from-milkWhite to-seashell dark:from-metalBlack dark:to-oilBlack border border-flasWhite dark:border-flasBlack">
                            <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey mb-2">Conclusión</h3>
                            <p class="mb-0">
                                Estudia Python con proyectos, mide tu dependencia de IA y construye evidencia pública (Git + artículos). Esa combinación es la que mejor te prepara para remoto y para un futuro donde quieras enseñar con autoridad.
                            </p>
                        </div>
""",
    },
    {
        "filename": "articulo-crm-trabajo-remoto-caracas-2026.html",
        "primary_tema": "crm",
        "pill": "CRM",
        "h1": "CRM y trabajo remoto desde Caracas: lo que la carrera no te contó sobre pipelines, pruebas y confianza",
        "date": "Febrero 2026",
        "meta": {
            "title": "CRM y trabajo remoto Caracas 2026: pipelines y confianza",
            "desc": "Artículo largo sobre CRM aplicado a búsqueda de trabajo remoto desde Venezuela: organización de leads, seguimiento, pruebas técnicas y comunicación con clientes internacionales.",
            "keywords": "CRM Caracas, trabajo remoto Venezuela, ventas digitales, pipeline leads, freelance Caracas, operaciones comerciales",
            "canonical_path": "articulo-crm-trabajo-remoto-caracas-2026.html",
        },
        "hero": {
            "src": "https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1600&q=82",
            "alt": "Equipo colaborando — CRM y trabajo remoto desde Venezuela",
        },
        "tag_links": tag_links(
            [
                ("crm", "CRM"),
                ("marketing", "Marketing"),
                ("it", "IT"),
                ("google", "Google"),
            ]
        ),
        "html": """
                        <p class="text-lg font-medium text-powerBlack dark:text-pastelGrey border-l-4 border-[var(--ensor-accent)] pl-4">
                            Este artículo une dos mundos: el <strong>CRM como sistema</strong> (herramientas, etapas, datos) y el <strong>CRM personal</strong> cuando buscas trabajo remoto desde Caracas. Spoiler: no es solo “mandar CV”; es gestionar relaciones como si fueras una micro-empresa.
                        </p>
                        <h2 class="text-2xl font-bold text-powerBlack dark:text-pastelGrey pt-4">Tu búsqueda de empleo es un pipeline</h2>
                        <p>
                            Etapas típicas: empresas objetivo, contacto inicial, prueba técnica, entrevista cultural, negociación. Si no lo registras, repites errores y pierdes oportunidades. Un sheet honesto ya es un CRM; lo importante es <strong>disciplina</strong> y notas contextuales (qué dijo el reclutador, qué stack usan, zona horaria).
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">Confianza internacional (y el sesgo que no se nombra)</h3>
                        <p>
                            Trabajar remoto desde Venezuela implica demostrar más con menos: portfolio claro, comunicación impecable en escrito, cumplir plazos. El CRM te ayuda a no “quemar puentes” por seguimientos desordenados o mensajes duplicados.
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">CRM en empresas vs CRM en servicios</h3>
                        <p>
                            En una empresa de Caracas que vende B2B, el CRM conecta marketing, ventas y operaciones. Si tú ofreces <strong>servicios</strong> (automatización, web, consultoría), tu CRM es aún más crítico: cada lead es un proyecto potencial y cada referencia cuenta para tu reputación local y remota.
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">Herramientas: no te cases con el nombre de moda</h3>
                        <p>
                            HubSpot, Pipedrive, Zoho, suites open source… lo relevante es entender <strong>campos, etapas, fuentes y reportes</strong>. Eso te hace enseñable: puedes dar un taller en una universidad venezolana sin depender de una licencia cara.
                        </p>
                        <div class="p-6 lg:p-8 rounded-xl xl:rounded-2xl bg-gradient-to-b from-milkWhite to-seashell dark:from-metalBlack dark:to-oilBlack border border-flasWhite dark:border-flasBlack">
                            <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey mb-2">Conclusión</h3>
                            <p class="mb-0">
                                Trata tu carrera como pipeline: mide, aprende, mejora plantillas de correo y seguimiento. El CRM no es “cosa de vendedores”: es orden profesional —y en 2026, orden es ventaja competitiva.
                            </p>
                        </div>
""",
    },
    {
        "filename": "articulo-sql-bases-datos-estudiante-trabajo-2026.html",
        "primary_tema": "database",
        "pill": "Database",
        "h1": "SQL y bases de datos en 2026: de la normalización en clase a la consulta lenta a medianoche en producción",
        "date": "Marzo 2026",
        "meta": {
            "title": "SQL y bases de datos 2026: estudiante y trabajo real Venezuela",
            "desc": "Guía extensa sobre aprendizaje de SQL y bases de datos en 2026: diferencias entre teoría académica, proyectos personales y desafíos reales en servidores y aplicaciones.",
            "keywords": "SQL Venezuela, bases de datos Caracas, PostgreSQL, estudiante IT, performance consultas, trabajo remoto database",
            "canonical_path": "articulo-sql-bases-datos-estudiante-trabajo-2026.html",
        },
        "hero": {
            "src": "https://images.unsplash.com/photo-1544383835-bda2bc66a55d?auto=format&fit=crop&w=1600&q=82",
            "alt": "Visualización de datos — SQL y bases de datos para desarrolladores",
        },
        "tag_links": tag_links(
            [
                ("database", "Database"),
                ("servidores", "Servidores"),
                ("linux", "Linux"),
                ("it", "IT"),
            ]
        ),
        "html": """
                        <p class="text-lg font-medium text-powerBlack dark:text-pastelGrey border-l-4 border-[var(--ensor-accent)] pl-4">
                            Si estudias ingeniería o afines en Caracas, probablemente viste normalización, álgebra relacional y diagramas ER. Bien. Ahora te hablo del otro lado: cuando una consulta en producción se traba y alguien te etiqueta en un grupo a las 12:30 a.m.
                        </p>
                        <h2 class="text-2xl font-bold text-powerBlack dark:text-pastelGrey pt-4">Teoría impecable, producción imperfecta</h2>
                        <p>
                            En clase los datasets son chicos; en vida real hay tablas creciendo, índices mal elegidos y ORMs que generan SQL feo. Aprender a leer <code class="bg-black/5 dark:bg-white/10 px-1 rounded">EXPLAIN</code>, entender locks básicos y conocer backups no es “extra”: es supervivencia profesional.
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">Postgres, MySQL, SQLite: decisiones prácticas</h3>
                        <p>
                            No existe una sola respuesta. Para prototipos y proyectos personales, SQLite puede ser oro; para concurrencia y reglas más duras, Postgres suele ser el favorito del ecosistema open source. Lo importante es que practiques <strong>migraciones</strong>, constraints y seeds reproducibles.
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">IA generando SQL: cuidado con la confianza ciega</h3>
                        <p>
                            En 2026 es tentador pedirle a un modelo que arme joins complejos. Úsalo, pero valida con datos reales y revisa costos. Un error clásico es mezclar agregaciones sin entender agrupaciones: en examen te bajan puntos; en producción bajan ventas.
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">Conexión con Linux y servidores</h3>
                        <p>
                            Muchas bases viven en <strong>Linux</strong>. Entender servicios, variables de entorno y túneles SSH te da autonomía cuando trabajas remoto para un cliente que no te va a “mapear” todo en Windows.
                        </p>
                        <div class="p-6 lg:p-8 rounded-xl xl:rounded-2xl bg-gradient-to-b from-milkWhite to-seashell dark:from-metalBlack dark:to-oilBlack border border-flasWhite dark:border-flasBlack">
                            <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey mb-2">Conclusión</h3>
                            <p class="mb-0">
                                Estudia SQL con proyectos que crezcan: primero correctitud, luego performance, luego operación (backups/restores). Esa progresión es la que te acerca a un perfil sólido para remoto y para enseñar con ejemplos reales.
                            </p>
                        </div>
""",
    },
    {
        "filename": "articulo-wordpress-optimizacion-cache-caracas-2026.html",
        "primary_tema": "wordpress",
        "pill": "WordPress",
        "h1": "WordPress rápido en Caracas: caching, plugins y decisiones de adulto para no convertir el sitio en Frankenstein",
        "date": "Marzo 2026",
        "meta": {
            "title": "WordPress optimización y caché 2026 Caracas Venezuela",
            "desc": "Segundo artículo WordPress: rendimiento web, plugins de caché, hosting y buenas prácticas para sitios en Venezuela con foco SEO local Caracas.",
            "keywords": "WordPress rápido Venezuela, caché WordPress, optimización web Caracas, Core Web Vitals, plugins WordPress rendimiento",
            "canonical_path": "articulo-wordpress-optimizacion-cache-caracas-2026.html",
        },
        "hero": {
            "src": "https://images.unsplash.com/photo-1504639725590-cec6a9de3e12?auto=format&fit=crop&w=1600&q=82",
            "alt": "Laptop en escritorio — optimización WordPress y productividad",
        },
        "tag_links": tag_links(
            [
                ("wordpress", "WordPress"),
                ("marketing", "Marketing"),
                ("google", "Google"),
                ("servidores", "Servidores"),
            ]
        ),
        "html": """
                        <p class="text-lg font-medium text-powerBlack dark:text-pastelGrey border-l-4 border-[var(--ensor-accent)] pl-4">
                            Este es mi <strong>segundo artículo dedicado a WordPress</strong> en esta serie: si el anterior hablaba de bloques y seguridad como base pedagógica, aquí entramos a la obsesión sana por velocidad —especialmente pensando en usuarios móviles en Caracas y en sitios que deben rankear.
                        </p>
                        <h2 class="text-2xl font-bold text-powerBlack dark:text-pastelGrey pt-4">Caché: página, objeto, CDN</h2>
                        <p>
                            No todo se soluciona instalando un plugin y rezando. Hay que entender qué se cachea, por cuánto tiempo y cómo invalidar cuando publicas contenido nuevo. Si enseñas a alguien, hazlo con un ejemplo medible: Lighthouse antes/después, tamaño de respuesta, TTFB.
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">Plugins: menos cantidad, más criterio</h3>
                        <p>
                            Cada plugin es deuda potencial: actualizaciones, conflictos, superficie de ataque. Para un cliente en Venezuela, prefiero pocas piezas confiables y monitoreo básico antes que un “zoológico” de extensiones.
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">Hosting y realidad del cable</h3>
                        <p>
                            Un servidor lejos mal configurado puede arruinar la experiencia aunque el tema sea bonito. Hablar de <strong>servidores</strong> y latencia no es snob: es empatía con el usuario final en Caracas.
                        </p>
                        <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey">SEO técnico mínimo viable</h3>
                        <p>
                            Sitemaps limpios, metadatos coherentes, URLs legibles, datos estructurados cuando aplica. Google no “premia trucos”; premia consistencia + velocidad + intención satisfecha. Si tu contenido habla de <strong>Caracas</strong> con propiedad, mejor aún.
                        </p>
                        <div class="p-6 lg:p-8 rounded-xl xl:rounded-2xl bg-gradient-to-b from-milkWhite to-seashell dark:from-metalBlack dark:to-oilBlack border border-flasWhite dark:border-flasBlack">
                            <h3 class="text-xl font-bold text-powerBlack dark:text-pastelGrey mb-2">Conclusión</h3>
                            <p class="mb-0">
                                Optimizar WordPress es un oficio: medir, cambiar una variable a la vez, documentar. Si quieres apoyo para auditar un sitio existente o montar un stack más liviano, puedes contactarme desde ENSOR.LOGS.
                            </p>
                        </div>
""",
    },
]


def build_related(current_fn, other):
    a, b = other
    return (
        RELATED_CARD.format(
            href=a["filename"],
            img=a["hero"]["src"].replace("w=1600", "w=900"),
            alt=a["hero"]["alt"],
            tema=a["primary_tema"],
            tag=a["pill"],
            title=a["h1"][:72] + ("…" if len(a["h1"]) > 72 else ""),
            excerpt="Artículo ENSOR.LOGS — lectura larga con enfoque Venezuela y Caracas.",
        )
        + RELATED_CARD.format(
            href=b["filename"],
            img=b["hero"]["src"].replace("w=1600", "w=900"),
            alt=b["hero"]["alt"],
            tema=b["primary_tema"],
            tag=b["pill"],
            title=b["h1"][:72] + ("…" if len(b["h1"]) > 72 else ""),
            excerpt="Bitácora técnica y pedagógica — Ensor Sánchez.",
        )
    )


def main():
    """Itera todos los artículos, genera HTML relacionado y escribe archivos."""
    n = len(ARTICLES)
    for i, art in enumerate(ARTICLES):
        others = [ARTICLES[(i + 1) % n], ARTICLES[(i + 2) % n]]
        art["related_html"] = build_related(art["filename"], others)
        page(art)
    print("Wrote", n, "articles to", OUT)


if __name__ == "__main__":
    main()

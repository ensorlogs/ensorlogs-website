<?php
/**
 * Metadatos de tarjetas (listados) alineados con el HTML estático.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return list<array{file:string,post_name:string,temas:string,primary_tema:string,card_image:string,card_excerpt:string,datetime:string}>
 */
function ensorlogs_seed_article_manifest(): array
{
    return array(
        array(
            'file'          => 'wordpress-vale-pena-aprender-2026.html',
            'post_name'     => 'wordpress-vale-pena-aprender-2026',
            'temas'         => 'wordpress marketing google ia it',
            'primary_tema'  => 'wordpress',
            'card_image'    => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=900&q=82',
            'card_excerpt'  => 'Mi experiencia real como estudiante, freelancer y alguien que todavía lo usa en proyectos reales: Venezuela, WordCamp Madrid, IA y por qué el contenido humano sigue importando.',
            'datetime'      => '2026-05-12 12:00:00',
        ),
        array(
            'file'          => 'wordpress-instalacion-ia-2026.html',
            'post_name'     => 'wordpress-instalacion-ia-2026',
            'temas'         => 'wordpress ia it servidores',
            'primary_tema'  => 'wordpress',
            'card_image'    => 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=900&q=82',
            'card_excerpt'  => 'Hosting, DNS, SSL, PHP y base de datos — más cómo usar IA como copiloto sin filtrar credenciales ni sustituir el criterio.',
            'datetime'      => '2026-05-12 12:00:00',
        ),
        array(
            'file'          => 'wordpress-seguridad-estudiantes-2026.html',
            'post_name'     => 'wordpress-seguridad-estudiantes-2026',
            'temas'         => 'wordpress ia it servidores',
            'primary_tema'  => 'wordpress',
            'card_image'    => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=900&q=82',
            'card_excerpt'  => 'Backups, plugins legítimos y hosting con cabeza; IA para checklists y logs siempre anonimizados — nunca pegues contraseñas al chat.',
            'datetime'      => '2026-05-12 12:00:00',
        ),
        array(
            'file'          => 'wordpress-rendimiento-estudiantes-2026.html',
            'post_name'     => 'wordpress-rendimiento-estudiantes-2026',
            'temas'         => 'wordpress ia google marketing',
            'primary_tema'  => 'wordpress',
            'card_image'    => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=900&q=82',
            'card_excerpt'  => 'Imágenes, caché y métricas sin marearte: uso la IA para ordenar informes, pero mido y priorizo según el proyecto real.',
            'datetime'      => '2026-05-12 12:00:00',
        ),
    );
}

/**
 * @return list<array{file:string,post_name:string,temas:string,item_class:string,img_rel:string,subtitle:string,title:string,tags:list<string>}>
 */
function ensorlogs_seed_project_manifest(): array
{
    return array(
        array(
            'file'        => 'proyecto-villa-martina-residences.html',
            'post_name'   => 'proyecto-villa-martina-residences',
            'temas'       => 'wordpress crm database it google marketing servidores',
            'item_class'  => 'project-item group sm:col-span-2',
            'img_rel'     => 'assets/img/projects/img1.png',
            'subtitle'    => 'PropTech · operaciones digitales',
            'title'       => 'Villa Martina Residences: implementación end-to-end para 110 unidades',
            'tags'        => array('wordpress', 'crm', 'database', 'it', 'google', 'marketing', 'servidores'),
        ),
        array(
            'file'        => 'proyecto-sport-city-club.html',
            'post_name'   => 'proyecto-sport-city-club',
            'temas'       => 'wordpress it servidores linux google',
            'item_class'  => 'project-item group',
            'img_rel'     => 'assets/img/projects/img2.png',
            'subtitle'    => 'Ecosistema digital · club deportivo',
            'title'       => 'Sport City Club: tecnología operativa desde anteproyecto',
            'tags'        => array('wordpress', 'it', 'servidores', 'linux', 'google'),
        ),
        array(
            'file'        => 'proyecto-solana-residences.html',
            'post_name'   => 'proyecto-solana-residences',
            'temas'       => 'wordpress servidores database it mac',
            'item_class'  => 'project-item group',
            'img_rel'     => 'assets/img/projects/img3.png',
            'subtitle'    => 'Transformación digital · residencial',
            'title'       => 'Solana Residences: infraestructura, web y soporte operativo',
            'tags'        => array('wordpress', 'servidores', 'database', 'it', 'mac'),
        ),
        array(
            'file'        => 'proyecto-apyca-developers.html',
            'post_name'   => 'proyecto-apyca-developers',
            'temas'       => 'crm it marketing python database',
            'item_class'  => 'project-item group sm:col-span-2',
            'img_rel'     => 'assets/img/projects/img4.png',
            'subtitle'    => 'Operaciones IT · PropTech',
            'title'       => 'APYCA Developers: +40% en eficiencia operativa',
            'tags'        => array('crm', 'it', 'marketing', 'python', 'database'),
        ),
        array(
            'file'        => 'proyecto-flujos-crm-captacion.html',
            'post_name'   => 'proyecto-flujos-crm-captacion',
            'temas'       => 'crm google marketing it',
            'item_class'  => 'project-item group',
            'img_rel'     => 'assets/img/projects/img5.png',
            'subtitle'    => 'Automatización CRM',
            'title'       => 'Flujos de captación y seguimiento comercial',
            'tags'        => array('crm', 'google', 'marketing', 'it'),
        ),
        array(
            'file'        => 'proyecto-dashboards-reporting.html',
            'post_name'   => 'proyecto-dashboards-reporting',
            'temas'       => 'database google it python marketing',
            'item_class'  => 'project-item group',
            'img_rel'     => 'assets/img/projects/img6.png',
            'subtitle'    => 'Analítica de negocio',
            'title'       => 'Dashboards y reporting para decisiones operativas',
            'tags'        => array('database', 'google', 'it', 'python', 'marketing'),
        ),
        array(
            'file'        => 'proyecto-infraestructura-soporte-it.html',
            'post_name'   => 'proyecto-infraestructura-soporte-it',
            'temas'       => 'linux servidores it windows mac',
            'item_class'  => 'project-item group',
            'img_rel'     => 'assets/img/projects/img7.png',
            'subtitle'    => 'Infraestructura IT',
            'title'       => 'Servidores, redes y soporte técnico avanzado',
            'tags'        => array('linux', 'servidores', 'it', 'windows', 'mac'),
        ),
    );
}

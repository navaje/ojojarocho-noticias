<?php
/**
 * Plugin Name: Ojo Jarocho Noticias
 * Description: Muestra las noticias locales capturadas por el bot en ojojarocho.online. Shortcodes: [noticias_hoy limite="12"] y [noticias_recientes n="5"]. Añade schema NewsArticle y geolocalización en las entradas de la categoría "Noticias locales".
 * Version: 1.0.0
 * Author: ojojarocho.online
 */

if (!defined('ABSPATH')) {
    exit;
}

function ojn_labels() {
    return array(
        'seguridad' => 'Seguridad',
        'vialidad'  => 'Alerta vial',
        'clima'     => 'Clima',
        'general'   => 'Noticia local',
    );
}

function ojn_emojis() {
    return array(
        'seguridad' => '🚨',
        'vialidad'  => '🚧',
        'clima'     => '⛈️',
        'general'   => '📰',
    );
}

function ojn_coords() {
    return array(
        'Cardel' => array(19.4308, -96.3692),
        'Alto Lucero' => array(19.5667, -96.4333),
        'La Antigua' => array(19.35, -96.13),
        'Puente Nacional' => array(19.3236, -96.4964),
        'Emiliano Zapata' => array(19.4167, -96.75),
        'Xalapa' => array(19.5438, -96.9103),
        'Veracruz' => array(19.1738, -96.1342),
        'Boca del Río' => array(19.1069, -96.1044),
        'Medellín' => array(18.9553, -96.1689),
        'Soledad de Doblado' => array(19.4167, -96.4167),
        'Actopan' => array(19.4961, -96.6261),
        'Coatepec' => array(19.4581, -96.9328),
        'Córdoba' => array(18.8939, -96.9347),
        'Orizaba' => array(18.8489, -97.1006),
        'Poza Rica' => array(20.5389, -97.4594),
        'Carretera Xalapa–Veracruz' => array(19.35, -96.6),
        'Carretera federal 140' => array(19.35, -96.6),
    );
}

add_action('init', function () {
    foreach (array('_oj_tipo', '_oj_label', '_oj_emoji', '_oj_lugar', '_oj_fuente', '_oj_url_fuente') as $meta) {
        register_post_meta('post', $meta, array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'string',
        ));
    }
});

function ojn_estilos() {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    echo '<style>
.ojn-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin:16px 0}
.ojn-card{display:block;background:#0f172a;color:#e2e8f0;border-radius:14px;padding:16px;text-decoration:none;border:1px solid #1e293b;transition:transform .15s ease,border-color .15s ease}
.ojn-card:hover{transform:translateY(-3px);border-color:#fde68a}
.ojn-badge{display:inline-block;font-size:.75rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;padding:4px 10px;border-radius:999px;margin-bottom:8px}
.ojn-seguridad{background:#7f1d1d;color:#fecaca}
.ojn-vialidad{background:#92400e;color:#fde68a}
.ojn-clima{background:#1e3a8a;color:#bfdbfe}
.ojn-general{background:#134e4a;color:#99f6e4}
.ojn-card h3{margin:0 0 8px;font-size:1rem;line-height:1.35;color:#f8fafc}
.ojn-meta{font-size:.8rem;color:#94a3b8;margin:0}
.ojn-lista{list-style:none;margin:12px 0;padding:0}
.ojn-lista li{display:flex;gap:10px;align-items:baseline;padding:10px 0;border-bottom:1px solid #e2e8f0}
.ojn-lista a{color:inherit;text-decoration:none;font-weight:600}
.ojn-lista a:hover{text-decoration:underline}
.ojn-lista .ojn-meta{white-space:nowrap;font-size:.78rem}
</style>';
}

function ojn_query($limite) {
    return new WP_Query(array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'category_name'  => 'noticias-locales',
        'posts_per_page' => max(1, intval($limite)),
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ));
}

function ojn_badge($tipo) {
    $labels = ojn_labels();
    $emojis = ojn_emojis();
    $tipo = sanitize_key($tipo ?: 'general');
    $label = isset($labels[$tipo]) ? $labels[$tipo] : $labels['general'];
    $emoji = isset($emojis[$tipo]) ? $emojis[$tipo] : $emojis['general'];
    return '<span class="ojn-badge ojn-' . esc_attr($tipo) . '">' . $emoji . ' ' . esc_html($label) . '</span>';
}

add_shortcode('noticias_hoy', function ($atts) {
    $atts = shortcode_atts(array('limite' => 12), $atts, 'noticias_hoy');
    ojn_estilos();
    $q = ojn_query($atts['limite']);
    if (!$q->have_posts()) {
        return '<p>No hay noticias locales por ahora. Vuelve pronto.</p>';
    }
    $html = '<div class="ojn-grid">';
    while ($q->have_posts()) {
        $q->the_post();
        $tipo = get_post_meta(get_the_ID(), '_oj_tipo', true);
        $lugar = get_post_meta(get_the_ID(), '_oj_lugar', true);
        $html .= '<a class="ojn-card" href="' . esc_url(get_permalink()) . '">'
            . ojn_badge($tipo)
            . '<h3>' . esc_html(get_the_title()) . '</h3>'
            . '<p class="ojn-meta">📍 ' . esc_html($lugar ?: 'Veracruz')
            . ' · 🕒 hace ' . esc_html(human_time_diff(get_post_time('U', true), current_time('timestamp', true))) . '</p>'
            . '</a>';
    }
    wp_reset_postdata();
    return $html . '</div>';
});

add_shortcode('noticias_recientes', function ($atts) {
    $atts = shortcode_atts(array('n' => 5), $atts, 'noticias_recientes');
    ojn_estilos();
    $q = ojn_query($atts['n']);
    if (!$q->have_posts()) {
        return '';
    }
    $html = '<ul class="ojn-lista">';
    while ($q->have_posts()) {
        $q->the_post();
        $tipo = get_post_meta(get_the_ID(), '_oj_tipo', true);
        $lugar = get_post_meta(get_the_ID(), '_oj_lugar', true);
        $html .= '<li>' . ojn_badge($tipo)
            . '<a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a>'
            . '<span class="ojn-meta">📍 ' . esc_html($lugar ?: 'Veracruz')
            . ' · hace ' . esc_html(human_time_diff(get_post_time('U', true), current_time('timestamp', true))) . '</span></li>';
    }
    wp_reset_postdata();
    return $html . '</ul>';
});

add_action('wp_head', function () {
    if (!is_single()) {
        return;
    }
    if (!has_category('noticias-locales')) {
        return;
    }
    $lugar = get_post_meta(get_the_ID(), '_oj_lugar', true);
    $fuente = get_post_meta(get_the_ID(), '_oj_fuente', true);
    $url_fuente = get_post_meta(get_the_ID(), '_oj_url_fuente', true);
    $coords = ojn_coords();
    $geo = isset($coords[$lugar]) ? $coords[$lugar] : $coords['Veracruz'];
    $img = get_the_post_thumbnail_url(get_the_ID(), 'full');
    $schema = array(
        '@context'    => 'https://schema.org',
        '@type'       => 'NewsArticle',
        'headline'    => get_the_title(),
        'datePublished' => get_post_time('c', true),
        'dateModified'  => get_post_modified_time('c', true),
        'image'       => $img ?: array(),
        'author'      => array('@type' => 'Organization', 'name' => 'ojojarocho.online'),
        'publisher'   => array(
            '@type' => 'Organization',
            'name'  => 'ojojarocho.online',
            'url'   => home_url('/'),
        ),
        'mainEntityOfPage' => get_permalink(),
        'articleSection'   => 'Noticias locales',
        'about' => array(
            '@type' => 'Place',
            'name'  => $lugar ?: 'Veracruz, México',
            'geo'   => array(
                '@type' => 'GeoCoordinates',
                'latitude'  => $geo[0],
                'longitude' => $geo[1],
            ),
        ),
    );
    if ($fuente && $url_fuente) {
        $schema['isBasedOn'] = $url_fuente;
    }
    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
});

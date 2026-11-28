<?php
if (!defined('ABSPATH')) { exit; }

add_action('init', function () {
    foreach (array('_oj_tipo', '_oj_label', '_oj_emoji', '_oj_lugar', '_oj_fuente', '_oj_url_fuente') as $meta) {
        register_post_meta('post', $meta, array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
        ));
    }
});

add_action('wp_head', function () {
    if (!is_single()) { return; }
    if (!has_category('noticias-locales')) { return; }
    $lugar = get_post_meta(get_the_ID(), '_oj_lugar', true);
    $fuente = get_post_meta(get_the_ID(), '_oj_fuente', true);
    $url_fuente = get_post_meta(get_the_ID(), '_oj_url_fuente', true);
    $coords = ojn_coords();
    $geo = isset($coords[$lugar]) ? $coords[$lugar] : $coords['Veracruz'];
    $img = get_the_post_thumbnail_url(get_the_ID(), 'full');
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'headline' => get_the_title(),
        'datePublished' => get_post_time('c', true),
        'dateModified' => get_post_modified_time('c', true),
        'image' => $img ? array($img) : array(),
        'author' => array('@type' => 'Organization', 'name' => 'ojojarocho.online'),
        'publisher' => array('@type' => 'Organization', 'name' => 'ojojarocho.online', 'url' => home_url('/')),
        'mainEntityOfPage' => get_permalink(),
        'articleSection' => 'Noticias locales',
        'about' => array(
            '@type' => 'Place',
            'name' => $lugar ?: 'Veracruz, México',
            'geo' => array(
                '@type' => 'GeoCoordinates',
                'latitude' => $geo[0],
                'longitude' => $geo[1],
            ),
        ),
    );
    if ($fuente && $url_fuente) { $schema['isBasedOn'] = $url_fuente; }
    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
});

<?php
/**
 * Plugin Name: Ojo Jarocho Noticias
 * Description: Bot de noticias locales de Veracruz (Cardel, Xalapa, Puerto) dentro de WordPress. Lee RSS cada 30 min (WP-Cron), filtra por zona, clasifica (vialidad/clima/seguridad/general), publica en la categoría "Noticias locales" con tarjeta propia, y cross-postea a Facebook/Instagram. No requiere terminal ni SSH. Shortcodes: [noticias_hoy limite="12"] y [noticias_recientes n="5"].
 * Version: 2.0.0
 * Author: ojojarocho.online
 */

if (!defined('ABSPATH')) { exit; }

define('OJN_DIR', plugin_dir_path(__FILE__));

require_once OJN_DIR . 'includes/config.php';
require_once OJN_DIR . 'includes/bot.php';
require_once OJN_DIR . 'includes/admin.php';
require_once OJN_DIR . 'includes/shortcodes.php';
require_once OJN_DIR . 'includes/schema.php';

add_filter('cron_schedules', function ($schedules) {
    $schedules['ojn_30min'] = array('interval' => 1800, 'display' => 'Cada 30 minutos (Ojo Jarocho Noticias)');
    return $schedules;
});

register_activation_hook(__FILE__, function () {
    if (!wp_next_scheduled('ojn_fetch_news')) {
        wp_schedule_event(time() + 60, 'ojn_30min', 'ojn_fetch_news');
    }
    if (get_option('ojn_seen') === false) { update_option('ojn_seen', array()); }
    if (get_option('ojn_auto_types') === false) { update_option('ojn_auto_types', ojn_default_auto_types()); }
    ojn_ensure_category();
});

register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('ojn_fetch_news');
});

add_action('ojn_fetch_news', function () {
    ojn_run_bot(false);
});

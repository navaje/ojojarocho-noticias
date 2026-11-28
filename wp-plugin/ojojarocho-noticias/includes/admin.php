<?php
if (!defined('ABSPATH')) { exit; }

add_action('admin_menu', function () {
    add_menu_page('Noticias bot', 'Noticias bot', 'manage_options', 'ojn-admin', 'ojn_admin_page', 'dashicons-megaphone', 58);
});

function ojn_admin_page() {
    if (isset($_POST['ojn_run']) && check_admin_referer('ojn_run')) {
        $r = ojn_run_bot(true);
        echo '<div class="notice notice-success"><p><strong>Ejecutado:</strong> ' . esc_html($r['msg']) . '</p></div>';
    }
    if (isset($_POST['ojn_save']) && check_admin_referer('ojn_save')) {
        update_option('ojn_fb_page_id', sanitize_text_field($_POST['ojn_fb_page_id'] ?? ''));
        update_option('ojn_fb_token', sanitize_text_field($_POST['ojn_fb_token'] ?? ''));
        update_option('ojn_ig_id', sanitize_text_field($_POST['ojn_ig_id'] ?? ''));
        update_option('ojn_hashtags', sanitize_text_field($_POST['ojn_hashtags'] ?? '#Cardel #Veracruz #Xalapa #NoticiasLocales'));
        $auto = isset($_POST['ojn_auto_types']) ? array_map('sanitize_key', $_POST['ojn_auto_types']) : array();
        update_option('ojn_auto_types', $auto);
        $feeds_en = array();
        foreach (ojn_feeds() as $f) {
            if (!empty($_POST['ojn_feed_on'][$f['name']])) { $feeds_en[$f['name']] = 1; }
        }
        update_option('ojn_feeds_enabled', $feeds_en);
        echo '<div class="notice notice-success"><p>Configuración guardada.</p></div>';
    }
    $last = get_option('ojn_last_run', 'nunca');
    $fb_page = get_option('ojn_fb_page_id', '');
    $fb_tok = get_option('ojn_fb_token', '');
    $ig = get_option('ojn_ig_id', '');
    $hashtags = get_option('ojn_hashtags', '#Cardel #Veracruz #Xalapa #NoticiasLocales');
    $auto = get_option('ojn_auto_types', ojn_default_auto_types());
    $enabled = get_option('ojn_feeds_enabled', null);
    $log = get_option('ojn_log', array());
    ?>
    <div class="wrap">
        <h1>📰 Noticias bot — ojojarocho.online</h1>
        <p>Última corrida automática: <strong><?php echo esc_html($last); ?></strong></p>
        <p>El bot corre solo cada 30 minutos (WP-Cron). Si tu sitio recibe visitas, se ejecuta al vuelo; si no, configura un cron externo que pegue <code>https://<?php echo esc_html(parse_url(home_url(), PHP_URL_HOST)); ?>/wp-cron.php</code> cada 30 min.</p>

        <h2>▶ Ejecutar ahora</h2>
        <form method="post">
            <?php wp_nonce_field('ojn_run'); ?>
            <input type="hidden" name="ojn_run" value="1">
            <p><button class="button button-primary">Buscar noticias y publicar ahora</button></p>
        </form>

        <h2>⚙ Configuración</h2>
        <form method="post">
            <?php wp_nonce_field('ojn_save'); ?>
            <input type="hidden" name="ojn_save" value="1">
            <table class="form-table">
                <tr><th scope="row">Facebook Page ID</th><td><input type="text" name="ojn_fb_page_id" value="<?php echo esc_attr($fb_page); ?>" class="regular-text"></td></tr>
                <tr><th scope="row">Facebook Page Access Token</th><td><input type="password" name="ojn_fb_token" value="<?php echo esc_attr($fb_tok); ?>" class="regular-text"> <span class="description">vacío = no publica en redes</span></td></tr>
                <tr><th scope="row">Instagram User ID</th><td><input type="text" name="ojn_ig_id" value="<?php echo esc_attr($ig); ?>" class="regular-text"></td></tr>
                <tr><th scope="row">Hashtags</th><td><input type="text" name="ojn_hashtags" value="<?php echo esc_attr($hashtags); ?>" class="regular-text"></td></tr>
            </table>
            <h3>Tipos que se publican solos (el resto queda pendiente de tu revisión)</h3>
            <?php foreach (ojn_tipos() as $key => $t): ?>
                <label style="margin-right:18px"><input type="checkbox" name="ojn_auto_types[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, (array) $auto, true)); ?>> <?php echo $t['emoji'] . ' ' . esc_html($t['label']); ?></label>
            <?php endforeach; ?>
            <h3>Fuentes (feeds) activas</h3>
            <?php foreach (ojn_feeds() as $f): ?>
                <label style="display:block;margin:2px 0"><input type="checkbox" name="ojn_feed_on[<?php echo esc_attr($f['name']); ?>]" value="1" <?php checked($enabled === null || !empty($enabled[$f['name']])); ?>> <?php echo esc_html($f['name']); ?> <code><?php echo esc_html($f['url']); ?></code></label>
            <?php endforeach; ?>
            <p><button class="button button-primary">Guardar configuración</button></p>
        </form>

        <h2>📋 Últimas corridas (log)</h2>
        <div style="background:#0b1220;color:#cbd5e1;padding:12px;border-radius:8px;max-height:320px;overflow:auto;font-family:monospace;font-size:12px">
            <?php foreach ($log as $line) { echo esc_html($line) . "<br>\n"; } ?>
        </div>
    </div>
    <?php
}

<?php
if (!defined('ABSPATH')) { exit; }

function ojn_log($msg) {
    $log = get_option('ojn_log', array());
    array_unshift($log, current_time('Y-m-d H:i:s') . '  ' . $msg);
    update_option('ojn_log', array_slice($log, 0, 120));
}

function ojn_normalize($s) {
    $s = mb_strtolower(trim((string) $s), 'UTF-8');
    $s = strtr($s, array('á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n','¡'=>'','¿'=>''));
    return trim(preg_replace('/\s+/u', ' ', $s));
}

function ojn_resumen($html, $max = 280) {
    $t = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES, 'UTF-8')));
    if (mb_strlen($t, 'UTF-8') <= $max) { return $t; }
    $cut = mb_substr($t, 0, $max, 'UTF-8');
    $pos = mb_strrpos($cut, ' ');
    if ($pos !== false) { $cut = mb_substr($cut, 0, $pos, 'UTF-8'); }
    return rtrim($cut, " ,;:") . '…';
}

function ojn_match($text, $key) {
    $key = ojn_normalize($key);
    if ($key === '') { return false; }
    if (substr($key, -1) === ' ') { return mb_strpos($text, $key) !== false; }
    return preg_match('/\b' . preg_quote($key, '/') . '\b/u', $text) === 1;
}

function ojn_classify($title, $body) {
    $text = ojn_normalize($title . ' ' . $body);
    $lugar = null;
    foreach (ojn_geo() as $g) {
        if (ojn_match($text, $g['key'])) { $lugar = $g['name']; break; }
    }
    $tipos = ojn_tipos();
    $tipo = 'general';
    foreach (ojn_tipo_order() as $name) {
        $kws = isset($tipos[$name]) ? $tipos[$name]['keywords'] : array();
        foreach ($kws as $k) {
            if (ojn_match($text, $k)) { $tipo = $name; break 2; }
        }
    }
    $auto_types = get_option('ojn_auto_types', ojn_default_auto_types());
    $auto = in_array($tipo, (array) $auto_types, true);
    return array(
        'local' => $lugar !== null,
        'lugar' => $lugar,
        'tipo' => $tipo,
        'label' => $tipos[$tipo]['label'],
        'emoji' => $tipos[$tipo]['emoji'],
        'auto' => $auto,
        'status' => $auto ? 'publish' : 'pending',
    );
}

function ojn_is_official($source_name) {
    $n = ojn_normalize($source_name);
    foreach (ojn_official_keywords() as $k) {
        if (mb_strpos($n, ojn_normalize($k)) !== false) { return true; }
    }
    return false;
}

function ojn_http_get($url) {
    $ua = 'Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0 ojojarocho-noticias-bot/2.0';
    $ctx = stream_context_create(array('http' => array(
        'timeout' => 20,
        'follow_location' => 1,
        'max_redirects' => 5,
        'header' => "User-Agent: $ua\r\nAccept: application/rss+xml, application/atom+xml, application/xml, text/xml, */*\r\n",
    ), 'ssl' => array('verify_peer' => false, 'verify_peer_name' => false)));
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        $e = error_get_last();
        ojn_log('streams err [' . ($e ? $e['message'] : 'desconocido') . '] ' . $url);
        return null;
    }
    return $body;
}

function ojn_fetch_direct($url) {
    $r = wp_remote_get($url, array(
        'timeout' => 20,
        'redirection' => 5,
        'sslverify' => false,
        'headers' => array(
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0 ojojarocho-noticias-bot/2.0',
            'Accept' => 'application/rss+xml, application/atom+xml, application/xml, text/xml, */*',
        ),
    ));
    if (is_wp_error($r)) {
        ojn_log('HTTP err [' . $r->get_error_message() . '] ' . $url);
        return ojn_http_get($url);
    }
    $code = (int) wp_remote_retrieve_response_code($r);
    if ($code >= 200 && $code < 300) { return wp_remote_retrieve_body($r); }
    ojn_log('HTTP ' . $code . ' ' . $url . ' -> reintento con streams');
    return ojn_http_get($url);
}

function ojn_fetch($url) {
    $body = ojn_fetch_direct($url);
    if ($body !== null) { return $body; }
    $proxy = 'https://api.allorigins.win/raw?url=' . urlencode($url);
    ojn_log('reintento via allorigins: ' . $url);
    return ojn_fetch_direct($proxy);
}

function ojn_parse_feed($xmlstr) {
    $xml = @simplexml_load_string($xmlstr, 'SimpleXMLElement', LIBXML_NOCDATA);
    if (!$xml) { return array(); }
    $items = array();
    if (isset($xml->channel)) {
        foreach ($xml->channel->item as $it) {
            $title = trim((string) $it->title);
            $link = trim((string) $it->link);
            if (!$link) { $link = trim((string) $it->guid); }
            $body = (string) $it->children('http://purl.org/rss/1.0/modules/content/')->encoded;
            if (!$body) { $body = (string) $it->description; }
            if ($title && $link) {
                $items[] = array('title' => $title, 'url' => $link, 'body' => $body, 'published' => (string) $it->pubDate);
            }
        }
    } else {
        foreach ($xml->entry as $it) {
            $title = trim((string) $it->title);
            $link = '';
            foreach ($it->link as $l) {
                $rel = (string) $l['rel'];
                if ($rel === 'alternate' || $rel === '') { $link = (string) $l['href']; break; }
            }
            if (!$link) { $link = trim((string) $it->id); }
            $body = (string) $it->content;
            if (!$body) { $body = (string) $it->summary; }
            if ($title && $link) {
                $items[] = array('title' => $title, 'url' => $link, 'body' => $body, 'published' => (string) $it->published);
            }
        }
    }
    return array_slice($items, 0, 25);
}

function ojn_seen_has($hash) {
    $seen = get_option('ojn_seen', array());
    return in_array($hash, $seen, true);
}

function ojn_mark_seen($hash) {
    $seen = get_option('ojn_seen', array());
    $seen[] = $hash;
    if (count($seen) > 1000) { $seen = array_slice($seen, -1000); }
    update_option('ojn_seen', $seen);
}

function ojn_wrap_text($text, $font, $size, $maxw, $draw = null) {
    $words = preg_split('/\s+/u', trim($text));
    $lines = array();
    $cur = '';
    foreach ($words as $w) {
        $t = trim($cur . ' ' . $w);
        if ($font) {
            $box = imagettfbbox($size, 0, $font, $t);
            $wpx = $box[2] - $box[0];
        } else {
            $wpx = strlen($t) * imagefontwidth(5);
        }
        if ($wpx <= $maxw || $cur === '') { $cur = $t; } else { $lines[] = $cur; $cur = $w; }
    }
    if ($cur !== '') { $lines[] = $cur; }
    return $lines;
}

function ojn_make_card_png($tipo, $label, $lugar, $titulo, $fuente, $fecha, $site) {
    if (!function_exists('imagecreatetruecolor')) { return null; }
    $w = 1200; $h = 630;
    $colors = array(
        'seguridad' => array(array(127,29,29), array(69,10,10)),
        'vialidad' => array(array(146,64,14), array(69,26,3)),
        'clima' => array(array(30,58,138), array(23,37,84)),
        'general' => array(array(19,78,74), array(4,47,46)),
    );
    list($top, $bottom) = isset($colors[$tipo]) ? $colors[$tipo] : $colors['general'];
    $img = imagecreatetruecolor($w, $h);
    for ($y = 0; $y < $h; $y++) {
        $r = $y / $h;
        $c = imagecolorallocate($img,
            (int) ($top[0] * (1 - $r) + $bottom[0] * $r),
            (int) ($top[1] * (1 - $r) + $bottom[1] * $r),
            (int) ($top[2] * (1 - $r) + $bottom[2] * $r));
        imageline($img, 0, $y, $w, $y, $c);
    }
    $dark = imagecolorallocate($img, 11, 18, 32);
    $white = imagecolorallocate($img, 248, 250, 252);
    $yellow = imagecolorallocate($img, 253, 230, 138);
    $gray = imagecolorallocate($img, 148, 163, 184);
    imagefilledrectangle($img, 0, 0, $w, 86, $dark);
    $font_b = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
    $font_r = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
    $fb = file_exists($font_b) ? $font_b : null;
    $fr = file_exists($font_r) ? $font_r : null;
    $text = function ($txt, $x, $y, $size, $color, $bold = true) use ($img, $fb, $fr) {
        if ($bold && $fb) { imagettftext($img, $size, 0, $x, $y, $color, $fb, $txt); }
        elseif (!$bold && $fr) { imagettftext($img, $size, 0, $x, $y, $color, $fr, $txt); }
        elseif ($bold) { imagestring($img, 5, $x, $y - 20, $txt, $color); }
        else { imagestring($img, 4, $x, $y - 16, $txt, $color); }
    };
    $text($site, 40, 58, 30, $white, true);
    $badge = mb_strtoupper($label, 'UTF-8');
    $text($badge, 58, 172, 34, $white, true);
    imagefilledrectangle($img, 40, 118, 40 + 420, 190, $dark);
    imagerectangle($img, 40, 118, 40 + 420, 190, $white);
    $text($badge, 58, 168, 34, $yellow, true);
    $text(mb_strtoupper((string) ($lugar ?: 'Veracruz'), 'UTF-8'), 40, 280, 52, $yellow, true);
    $lines = array_slice(ojn_wrap_text($titulo, $fr, 30, $w - 80), 0, 4);
    $y = 340;
    foreach ($lines as $ln) { $text($ln, 40, $y, 30, $white, false); $y += 44; }
    imagefilledrectangle($img, 0, $h - 70, $w, $h, $dark);
    $text('📅 ' . $fecha . '   ·   Fuente: ' . $fuente . ' · noticia en desarrollo', 40, $h - 30, 22, $gray, false);
    ob_start();
    imagepng($img);
    $bin = ob_get_clean();
    imagedestroy($img);
    return $bin;
}

function ojn_upload_card($png_bin, $titulo) {
    $up = wp_upload_bits('ojn-card-' . time() . '.png', null, $png_bin);
    if (!empty($up['error'])) { return null; }
    $att_id = wp_insert_attachment(array(
        'post_mime_type' => 'image/png',
        'post_title' => sanitize_text_field($titulo),
        'post_content' => '',
        'post_status' => 'inherit',
    ), $up['file']);
    if (!is_wp_error($att_id) && $att_id) {
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        wp_update_attachment_metadata($att_id, wp_generate_attachment_metadata($att_id, $up['file']));
    }
    return array('id' => $att_id, 'url' => $up['url']);
}

function ojn_ensure_category() {
    $cat = get_category_by_slug('noticias-locales');
    if ($cat) { return (int) $cat->term_id; }
    $r = wp_insert_term('Noticias locales', 'category', array('slug' => 'noticias-locales'));
    if (is_wp_error($r)) { return 0; }
    return (int) $r['term_id'];
}

function ojn_build_content($lugar, $resumen, $fuente, $url_fuente, $oficial, $emoji) {
    $oficial_html = $oficial ? '<p><strong>✅ Fuente oficial</strong></p>' : '';
    return '<p><strong>📍 ' . esc_html($lugar) . '</strong></p>'
        . '<p>' . esc_html($resumen) . '</p>'
        . $oficial_html
        . '<p>Fuente: <a href="' . esc_url($url_fuente) . '" target="_blank" rel="noopener nofollow">' . esc_html($fuente) . '</a></p>'
        . '<p><em>Noticia en desarrollo. Esta entrada se actualiza con la información verificada. ' . $emoji . '</em></p>';
}

function ojn_social_crosspost($emoji, $label, $lugar, $titulo, $post_url, $fuente, $image_url) {
    $page = get_option('ojn_fb_page_id', '');
    $tok = get_option('ojn_fb_token', '');
    $ig = get_option('ojn_ig_id', '');
    $hashtags = get_option('ojn_hashtags', '#Cardel #Veracruz #Xalapa #NoticiasLocales');
    if (!$page || !$tok) { ojn_log('Social deshabilitado (faltan FB_PAGE_ID / token)'); return; }
    $msg = $emoji . ' ' . $label . ' en ' . $lugar . "\n\n" . $titulo . "\n\n🔗 Leer en ojojarocho.online: " . $post_url . "\n\nFuente: " . $fuente . "\n" . $hashtags;
    $r = wp_remote_post('https://graph.facebook.com/v21.0/' . $page . '/feed', array(
        'timeout' => 30,
        'body' => array('message' => $msg, 'link' => $post_url, 'access_token' => $tok),
    ));
    if (is_wp_error($r)) { ojn_log('Error FB: ' . $r->get_error_message()); } else { ojn_log('Publicado en Facebook: ' . $post_url); }
    if ($ig && $image_url) {
        $r1 = wp_remote_post('https://graph.facebook.com/v21.0/' . $ig . '/media', array(
            'timeout' => 30,
            'body' => array('image_url' => $image_url, 'caption' => $msg, 'access_token' => $tok),
        ));
        $j1 = json_decode(wp_remote_retrieve_body($r1), true);
        if (!empty($j1['id'])) {
            $cid = $j1['id'];
            for ($i = 0; $i < 10; $i++) {
                $c = wp_remote_get('https://graph.facebook.com/v21.0/' . $cid . '?fields=status_code&access_token=' . $tok, array('timeout' => 20));
                $cj = json_decode(wp_remote_retrieve_body($c), true);
                if (isset($cj['status_code']) && $cj['status_code'] === 'FINISHED') { break; }
                sleep(3);
            }
            $r2 = wp_remote_post('https://graph.facebook.com/v21.0/' . $ig . '/media_publish', array(
                'timeout' => 30,
                'body' => array('creation_id' => $cid, 'access_token' => $tok),
            ));
            if (is_wp_error($r2)) { ojn_log('Error IG publish: ' . $r2->get_error_message()); } else { ojn_log('Publicado en Instagram: ' . $post_url); }
        } else {
            ojn_log('Error IG media: ' . wp_remote_retrieve_body($r1));
        }
    }
}

function ojn_run_bot($manual = false) {
    $max_total = 10;
    $published = 0; $pending = 0; $skipped = 0;
    $cat_id = ojn_ensure_category();
    $enabled = get_option('ojn_feeds_enabled', null);
    $site = parse_url(home_url(), PHP_URL_HOST);
    $fecha = current_time('d M Y · H:i');
    foreach (ojn_feeds() as $feed) {
        if ($enabled !== null && empty($enabled[$feed['name']])) { continue; }
        $xmlstr = ojn_fetch($feed['url']);
        if ($xmlstr === null) { ojn_log('Feed falló: ' . $feed['name']); continue; }
        $items = ojn_parse_feed($xmlstr);
        if (!$items) { ojn_log('Feed sin entradas: ' . $feed['name']); continue; }
        foreach ($items as $item) {
            if ($published + $pending >= $max_total) { break 2; }
            $cls = ojn_classify($item['title'], $item['body']);
            if (!$cls['local']) { $skipped++; continue; }
            $hash = sha1(ojn_normalize($item['url'] ? $item['url'] : $item['title']));
            if (ojn_seen_has($hash)) { $skipped++; continue; }
            $oficial = ojn_is_official($feed['name']);
            $resumen = ojn_resumen($item['body']);
            $titulo = $cls['emoji'] . ' ' . $item['title'];
            $content = ojn_build_content($cls['lugar'], $resumen, $feed['name'], $item['url'], $oficial, $cls['emoji']);
            $post_id = wp_insert_post(array(
                'post_title' => $titulo,
                'post_content' => $content,
                'post_status' => $cls['status'],
                'post_type' => 'post',
                'post_category' => array($cat_id),
                'tags_input' => array_filter(array('noticias locales', 'veracruz', $cls['lugar'], $cls['label'])),
                'post_name' => sanitize_title($item['title']) . '-' . substr(md5($item['url']), 0, 6),
                'meta_input' => array(
                    '_oj_tipo' => $cls['tipo'],
                    '_oj_label' => $cls['label'],
                    '_oj_emoji' => $cls['emoji'],
                    '_oj_lugar' => (string) $cls['lugar'],
                    '_oj_fuente' => $feed['name'],
                    '_oj_url_fuente' => $item['url'],
                ),
            ), true);
            if (is_wp_error($post_id)) { ojn_log('Error al crear post: ' . $post_id->get_error_message()); continue; }
            $media = null;
            $png = ojn_make_card_png($cls['tipo'], $cls['label'], $cls['lugar'], $item['title'], $feed['name'], $fecha, $site);
            if ($png) {
                $media = ojn_upload_card($png, $titulo);
                if ($media && !empty($media['id'])) { set_post_thumbnail($post_id, $media['id']); }
            }
            ojn_mark_seen($hash);
            $post_url = get_permalink($post_id);
            if ($cls['status'] === 'publish') {
                $published++;
                ojn_log('PUBLICADO: ' . $post_url . ' (' . $cls['lugar'] . ')');
                ojn_social_crosspost($cls['emoji'], $cls['label'], $cls['lugar'], $item['title'], $post_url, $feed['name'], $media ? $media['url'] : null);
            } else {
                $pending++;
                ojn_log('PENDIENTE (revisión): ID ' . $post_id . ' — ' . $item['title']);
            }
        }
    }
    update_option('ojn_last_run', current_time('mysql'));
    $msg = 'Corrida ' . ($manual ? '(manual)' : '(cron)') . ": publicadas=$published pendientes=$pending omitidas=$skipped";
    ojn_log($msg);
    return array('published' => $published, 'pending' => $pending, 'skipped' => $skipped, 'msg' => $msg);
}

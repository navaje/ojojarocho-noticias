<?php
if (!defined('ABSPATH')) { exit; }

function ojn_feeds() {
    $gn = '?hl=es-419&gl=MX&ceid=MX:es-419';
    return array(
        array('name' => 'Palabras Claras (Veracruz)', 'url' => 'https://palabrasclaras.mx/feed/'),
        array('name' => 'Google News: Cardel', 'url' => 'https://news.google.com/rss/search?q=Cardel%20Veracruz&hl=es-419&gl=MX&ceid=MX:es-419'),
        array('name' => 'Google News: alrededores Cardel', 'url' => 'https://news.google.com/rss/search?q=Alto%20Lucero%20Veracruz%20OR%20La%20Antigua%20Veracruz%20OR%20Puente%20Nacional%20Veracruz%20OR%20Emiliano%20Zapata%20Veracruz' . $gn),
        array('name' => 'Google News: Xalapa', 'url' => 'https://news.google.com/rss/search?q=Xalapa%20Veracruz' . $gn),
        array('name' => 'Google News: Veracruz puerto', 'url' => 'https://news.google.com/rss/search?q=Veracruz%20puerto%20OR%20Boca%20del%20R%C3%ADo%20OR%20Medell%C3%ADn' . $gn),
        array('name' => 'Google News: carretera Xalapa-Veracruz', 'url' => 'https://news.google.com/rss/search?q=carretera%20Xalapa%20Veracruz%20accidente%20OR%20cierre%20OR%20bloqueo%20OR%20tr%C3%A1fico%20OR%20carretera%20140' . $gn),
        array('name' => 'Google News: vialidad zona centro', 'url' => 'https://news.google.com/rss/search?q=accidente%20OR%20choque%20OR%20volcadura%20OR%20cierre%20carretera%20Veracruz%20Xalapa%20Cardel' . $gn),
        array('name' => 'Google News: nota roja zona', 'url' => 'https://news.google.com/rss/search?q=homicidio%20OR%20balacera%20OR%20asesinato%20OR%20secuestro%20Xalapa%20OR%20Veracruz%20OR%20Cardel%20OR%20Alto%20Lucero' . $gn),
        array('name' => 'Google News: clima Veracruz', 'url' => 'https://news.google.com/rss/search?q=frente%20fr%C3%ADo%20OR%20alerta%20OR%20inundaci%C3%B3n%20OR%20deslave%20OR%20derrumbes%20Veracruz' . $gn),
    );
}

function ojn_geo() {
    return array(
        array('key' => 'cardel', 'name' => 'Cardel'),
        array('key' => 'alto lucero', 'name' => 'Alto Lucero'),
        array('key' => 'la antigua', 'name' => 'La Antigua'),
        array('key' => 'puente nacional', 'name' => 'Puente Nacional'),
        array('key' => 'emiliano zapata', 'name' => 'Emiliano Zapata'),
        array('key' => 'el lencero', 'name' => 'El Lencero, Emiliano Zapata'),
        array('key' => 'las trancas', 'name' => 'Las Trancas'),
        array('key' => 'plan del rio', 'name' => 'Plan del Río'),
        array('key' => 'xalapa-veracruz', 'name' => 'Carretera Xalapa–Veracruz'),
        array('key' => 'veracruz-xalapa', 'name' => 'Carretera Veracruz–Xalapa'),
        array('key' => 'carretera 140', 'name' => 'Carretera federal 140'),
        array('key' => 'ruta 140', 'name' => 'Carretera federal 140'),
        array('key' => 'xalapa', 'name' => 'Xalapa'),
        array('key' => 'coatepec', 'name' => 'Coatepec'),
        array('key' => 'xico', 'name' => 'Xico'),
        array('key' => 'teocelo', 'name' => 'Teocelo'),
        array('key' => 'las vigas', 'name' => 'Las Vigas'),
        array('key' => 'perote', 'name' => 'Perote'),
        array('key' => 'naolinco', 'name' => 'Naolinco'),
        array('key' => 'banderilla', 'name' => 'Banderilla'),
        array('key' => 'misantla', 'name' => 'Misantla'),
        array('key' => 'actopan', 'name' => 'Actopan'),
        array('key' => 'veracruz', 'name' => 'Veracruz'),
        array('key' => 'boca del rio', 'name' => 'Boca del Río'),
        array('key' => 'medellin', 'name' => 'Medellín'),
        array('key' => 'soledad de doblado', 'name' => 'Soledad de Doblado'),
        array('key' => 'alvarado', 'name' => 'Alvarado'),
        array('key' => 'lerdo de tejada', 'name' => 'Lerdo de Tejada'),
        array('key' => 'ignacio de la llave', 'name' => 'Ignacio de la Llave'),
        array('key' => 'puerto de veracruz', 'name' => 'Puerto de Veracruz'),
        array('key' => 'zona conurbada', 'name' => 'Zona conurbada Veracruz–Boca del Río'),
        array('key' => 'zona centro', 'name' => 'Zona centro de Veracruz'),
        array('key' => 'cordoba', 'name' => 'Córdoba'),
        array('key' => 'orizaba', 'name' => 'Orizaba'),
        array('key' => 'fortin', 'name' => 'Fortín'),
        array('key' => 'poza rica', 'name' => 'Poza Rica'),
        array('key' => 'martinez de la torre', 'name' => 'Martínez de la Torre'),
        array('key' => 'tuxpan', 'name' => 'Tuxpan'),
        array('key' => 'papantla', 'name' => 'Papantla'),
    );
}

function ojn_tipos() {
    return array(
        'seguridad' => array(
            'label' => 'Seguridad', 'emoji' => '🚨',
            'keywords' => array('asesinato','homicidio','feminicidio','feminicida','baleo','balacera','ejecutado','ejecucion','secuestro','desaparecido','desaparicion','levanton','narco','cartel','crimen organizado','asalto','robo a mano armada','sicario','extorsion','sin vida','hallan cuerpo','cadaver','fosa clandestina','violencia','delictivo'),
        ),
        'vialidad' => array(
            'label' => 'Alerta vial', 'emoji' => '🚧',
            'keywords' => array('accidente','choque','carambola','volcadura','volcado','atropellado','lesionado','cierre de circulacion','carretera cerrada','via cerrada','cierre vehicular','bloqueo','manifestacion en carretera','trafico','transito','congestion vial','derrumbes','deslizamiento','socavon','bache','puente danado','vialidad','carretera','autopista','km '),
        ),
        'clima' => array(
            'label' => 'Clima', 'emoji' => '⛈️',
            'keywords' => array('frente frio','evento de norte','lluvias','lluvia','inundacion','granizo','huracan','tormenta','ola de calor','nevada','conagua','smn','alerta gris','alerta amarilla','contingencia','vientos','deslave','crecida de rio','rio desborda'),
        ),
        'general' => array('label' => 'Noticia local', 'emoji' => '📰', 'keywords' => array()),
    );
}

function ojn_tipo_order() {
    return array('seguridad', 'vialidad', 'clima', 'general');
}

function ojn_default_auto_types() {
    return array('vialidad', 'clima');
}

function ojn_official_keywords() {
    return array('proteccion civil','gobierno del estado','secretaria de seguridad publica','seguridad publica del estado','policia','vialidad','transito','c5','911','fiscalia','fge','conagua','smn');
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

<?php
// inc/asistencias/asistencia-seed.php
if (!defined('ABSPATH')) exit;

/**
 * Menú: Asistencia -> Datos de prueba
 */
add_action('admin_menu', function () {
    add_submenu_page(
        'asistencia_buscar',
        'Datos de prueba',
        'Datos de prueba',
        'manage_options',
        'asistencia_seed',
        'asistencia_seed_page'
    );
});

function asistencia_seed_page() {
    if (!current_user_can('manage_options')) wp_die('Sin permisos.');

    echo '<div class="wrap"><h1>Datos de prueba (Asistencia)</h1>';

    if (isset($_GET['seed_ok']) && $_GET['seed_ok'] === '1') {
        echo '<div class="notice notice-success"><p>Datos insertados correctamente.</p></div>';
    }
    if (isset($_GET['seed_ok']) && $_GET['seed_ok'] === '0') {
        $msg = isset($_GET['seed_err']) ? sanitize_text_field(wp_unslash($_GET['seed_err'])) : 'Error desconocido';
        echo '<div class="notice notice-error"><p>Error: <code>' . esc_html($msg) . '</code></p></div>';
    }

    echo '<p>Esto insertará: empresas, proyectos, 15 técnicos (N1/N2/N3) y asignaciones.</p>';

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="asistencia_seed" />';
    wp_nonce_field('asistencia_seed_now');
    echo '<label style="display:block;margin:10px 0;">';
    echo '<input type="checkbox" name="wipe" value="1"> Borrar datos previos (TRUNCATE) antes de insertar';
    echo '</label>';
    submit_button('Cargar datos de prueba', 'primary');
    echo '</form>';

    echo '</div>';
}

/**
 * Handler POST
 */
add_action('admin_post_asistencia_seed', function () {
    if (!current_user_can('manage_options')) wp_die('Sin permisos.');
    check_admin_referer('asistencia_seed_now'); // protege el POST [web:2686]

    $wipe = !empty($_POST['wipe']);

    $err = asistencia_seed_insert($wipe);

    $url = add_query_arg([
        'page' => 'asistencia_seed',
        'seed_ok' => $err ? '0' : '1',
        'seed_err' => $err ? rawurlencode($err) : '',
    ], admin_url('admin.php'));

    wp_safe_redirect($url);
    exit;
});

function asistencia_seed_insert($wipe = false) {
    if (!function_exists('asistencia_tables')) {
        return 'No existe asistencia_tables() (¿está cargado asistencia-install.php?)';
    }

    global $wpdb;
    $t = asistencia_tables();

    // Comprobar tablas
    foreach ($t as $table) {
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists !== $table) {
            return "Falta la tabla $table. Pulsa primero: Asistencia -> Instalar/Reparar tablas";
        }
    }

    // (Opcional) limpiar datos
    if ($wipe) {
        // Orden: relaciones -> tablas principales
        $wpdb->query("TRUNCATE TABLE {$t['tecnico_proyecto']}");
        $wpdb->query("TRUNCATE TABLE {$t['tecnico_empresa']}");
        $wpdb->query("TRUNCATE TABLE {$t['proyectos']}");
        $wpdb->query("TRUNCATE TABLE {$t['tecnicos']}");
        $wpdb->query("TRUNCATE TABLE {$t['empresas']}");
    }

    // Evitar duplicar sin wipe
    $count_emp = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t['empresas']}");
    if ($count_emp > 0 && !$wipe) {
        return 'Ya hay empresas. Marca “Borrar datos previos” para re-cargar.';
    }

    $now = current_time('mysql');

    // Empresas (10)
    $empresas = [
        'Seidor', 'Aglinformatica5', 'Empresa Demo', 'NetSolutions', 'CloudMancha',
        'SoportePlus', 'InnovaTech', 'DataWorks', 'HelpDesk SL', 'Proyecto Cuenca'
    ];

    $empresa_ids = [];
    foreach ($empresas as $name) {
        $ok = $wpdb->insert($t['empresas'], ['nombre' => $name, 'created_at' => $now]); // $wpdb->insert [web:2679]
        if (!$ok) return 'Insert empresa falló: ' . $wpdb->last_error;
        $empresa_ids[] = (int) $wpdb->insert_id;
    }

    // Proyectos: 2 por empresa (20)
    $proyecto_ids_por_empresa = [];
    foreach ($empresa_ids as $eid) {
        $nombres = ['Soporte', 'Migración'];
        $ids = [];
        foreach ($nombres as $base) {
            $ok = $wpdb->insert($t['proyectos'], [
                'empresa_id' => $eid,
                'nombre' => $base . ' ' . $eid,
                'created_at' => $now
            ]);
            if (!$ok) return 'Insert proyecto falló: ' . $wpdb->last_error;
            $ids[] = (int) $wpdb->insert_id;
        }
        $proyecto_ids_por_empresa[$eid] = $ids;
    }

    // Técnicos (15): N1=5 (no asignados), N2=5, N3=5
    $tecnicos = [];
    for ($i=1; $i<=5; $i++) $tecnicos[] = ['nombre'=>"Tecnico N1-$i", 'nivel'=>1];
    for ($i=1; $i<=5; $i++) $tecnicos[] = ['nombre'=>"Tecnico N2-$i", 'nivel'=>2];
    for ($i=1; $i<=5; $i++) $tecnicos[] = ['nombre'=>"Tecnico N3-$i", 'nivel'=>3];

    $tecnico_ids_n2 = [];
    $tecnico_ids_n3 = [];

    foreach ($tecnicos as $tec) {
        $ok = $wpdb->insert($t['tecnicos'], [
            'wp_user_id' => null,
            'nombre' => $tec['nombre'],
            'nivel' => (int)$tec['nivel'],
            'activo' => 1,
            'created_at' => $now
        ]);
        if (!$ok) return 'Insert técnico falló: ' . $wpdb->last_error;

        $tid = (int)$wpdb->insert_id;
        if ($tec['nivel'] === 2) $tecnico_ids_n2[] = $tid;
        if ($tec['nivel'] === 3) $tecnico_ids_n3[] = $tid;
    }

    // Asignar técnicos a empresas:
    // Cada empresa: 1 N3 + 1 N2 (rotando), N1 no se asigna
    $idx2 = 0;
    $idx3 = 0;

    foreach ($empresa_ids as $eid) {
        $t2 = $tecnico_ids_n2[$idx2 % count($tecnico_ids_n2)];
        $t3 = $tecnico_ids_n3[$idx3 % count($tecnico_ids_n3)];
        $idx2++; $idx3++;

        $ok = $wpdb->insert($t['tecnico_empresa'], ['tecnico_id'=>$t2, 'empresa_id'=>$eid]);
        if (!$ok) return 'Asignación técnico-empresa (N2) falló: ' . $wpdb->last_error;

        $ok = $wpdb->insert($t['tecnico_empresa'], ['tecnico_id'=>$t3, 'empresa_id'=>$eid]);
        if (!$ok) return 'Asignación técnico-empresa (N3) falló: ' . $wpdb->last_error;

        // (Opcional) asignar esos técnicos a los proyectos de esa empresa
        foreach ($proyecto_ids_por_empresa[$eid] as $pid) {
            $wpdb->insert($t['tecnico_proyecto'], ['tecnico_id'=>$t2, 'proyecto_id'=>$pid]);
            $wpdb->insert($t['tecnico_proyecto'], ['tecnico_id'=>$t3, 'proyecto_id'=>$pid]);
        }
    }

    return ''; // OK
}

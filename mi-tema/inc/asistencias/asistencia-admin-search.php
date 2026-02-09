<?php
// inc/asistencias/asistencia-admin-search.php

if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_menu_page(
        'Asistencia',
        'Asistencia',
        'manage_options',
        'asistencia_buscar',
        'asistencia_admin_buscar_page',
        'dashicons-search',
        26
    );
});

function asistencia_admin_buscar_page() {
    if (!current_user_can('manage_options')) wp_die('Sin permisos.');

    global $wpdb;
    $t = function_exists('asistencia_tables') ? asistencia_tables() : [];

    echo '<div class="wrap"><h1>Buscar empresa (Asistencia)</h1>';

    // Aviso de instalación manual
    if (isset($_GET['as_installed'])) {
        if ($_GET['as_installed'] === '1') {
            echo '<div class="notice notice-success"><p>Tablas instaladas/actualizadas.</p></div>';
        } else {
            $msg = isset($_GET['as_error']) ? sanitize_text_field(wp_unslash($_GET['as_error'])) : 'Error desconocido';
            echo '<div class="notice notice-error"><p>Error instalando tablas: <code>' . esc_html($msg) . '</code></p></div>';
        }
    }

    // Botón instalar/reparar (no depende de after_switch_theme). [web:2642]
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin:10px 0;">';
    echo '<input type="hidden" name="action" value="asistencia_install" />';
    wp_nonce_field('asistencia_install_now');
    submit_button('Instalar / Reparar tablas', 'secondary');
    echo '</form>';

    if (empty($t)) {
        echo '<div class="notice notice-error"><p>No se cargó el instalador (falta include en functions.php).</p></div></div>';
        return;
    }

    // Comprobar existencia de tablas
    $missing = [];
    foreach ($t as $name => $table) {
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists !== $table) $missing[] = $table;
    }
    if ($missing) {
        echo '<div class="notice notice-warning"><p>Faltan tablas: <code>' . esc_html(implode(', ', $missing)) . '</code></p></div>';
    }

    // Form de búsqueda
    $term = isset($_GET['empresa']) ? sanitize_text_field($_GET['empresa']) : '';
    echo '<form method="get" style="margin:12px 0;">';
    echo '<input type="hidden" name="page" value="asistencia_buscar" />';
    echo '<input type="search" name="empresa" value="' . esc_attr($term) . '" placeholder="Empieza por... (mejor para índices)" style="width:320px;" />';
    submit_button('Buscar', 'primary', '', false);
    echo '</form>';

    // Mostrar índices (si la tabla existe)
    echo '<h2>Índices</h2>';
    asistencia_print_indexes('as_empresas', $t['empresas']);
    asistencia_print_indexes('as_proyectos', $t['proyectos']);
    asistencia_print_indexes('as_tecnicos', $t['tecnicos']);
    asistencia_print_indexes('as_tecnico_empresa', $t['tecnico_empresa']);

    // Búsqueda por prefijo (term%) para favorecer el índice de nombre.
    if ($term !== '' && empty($missing)) {
        $like = $wpdb->esc_like($term) . '%';

        $empresas = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nombre FROM {$t['empresas']} WHERE nombre LIKE %s ORDER BY nombre LIMIT 20",
            $like
        ));

        echo '<h2>Empresas encontradas</h2>';
        if (!$empresas) {
            echo '<p>No hay coincidencias.</p>';
        } else {
            foreach ($empresas as $e) {
                echo '<hr>';
                echo '<h3>' . esc_html($e->nombre) . ' (ID ' . intval($e->id) . ')</h3>';

                // Proyectos
                $proyectos = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, nombre FROM {$t['proyectos']} WHERE empresa_id = %d ORDER BY nombre",
                    $e->id
                ));
                echo '<h4>Proyectos</h4>';
                if (!$proyectos) {
                    echo '<p>Sin proyectos.</p>';
                } else {
                    echo '<ul>';
                    foreach ($proyectos as $p) {
                        echo '<li>' . esc_html($p->nombre) . ' (ID ' . intval($p->id) . ')</li>';
                    }
                    echo '</ul>';
                }

                // Técnicos N2/N3 asignados a la empresa
                $sql_tecnicos = $wpdb->prepare(
                    "SELECT t.id, t.nombre, t.nivel
                     FROM {$t['tecnico_empresa']} te
                     INNER JOIN {$t['tecnicos']} t ON t.id = te.tecnico_id
                     WHERE te.empresa_id = %d AND t.nivel IN (2,3)
                     ORDER BY t.nivel DESC, t.nombre",
                    $e->id
                );
                $tecnicos = $wpdb->get_results($sql_tecnicos);

                echo '<h4>Técnicos asignados (N2/N3)</h4>';
                if (!$tecnicos) {
                    echo '<p>Sin técnicos N2/N3 asignados.</p>';
                } else {
                    echo '<ul>';
                    foreach ($tecnicos as $tt) {
                        $nivel = ($tt->nivel == 2) ? 'N2' : 'N3';
                        echo '<li>' . esc_html($tt->nombre) . ' - ' . esc_html($nivel) . ' (ID ' . intval($tt->id) . ')</li>';
                    }
                    echo '</ul>';
                }
            }
        }
    }

    echo '</div>';
}

// Índices de una tabla (SHOW INDEX)
function asistencia_print_indexes($label, $table) {
    global $wpdb;

    echo '<h4>' . esc_html($label) . '</h4>';

    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if ($exists !== $table) {
        echo '<p>(Tabla no existe)</p>';
        return;
    }

    $idx = $wpdb->get_results("SHOW INDEX FROM $table");
    if (!$idx) {
        echo '<p>(Sin índices)</p>';
        return;
    }

    echo '<table class="widefat striped"><thead><tr>
        <th>Key_name</th><th>Column_name</th><th>Non_unique</th><th>Index_type</th>
    </tr></thead><tbody>';

    foreach ($idx as $i) {
        echo '<tr>';
        echo '<td>' . esc_html($i->Key_name) . '</td>';
        echo '<td>' . esc_html($i->Column_name) . '</td>';
        echo '<td>' . esc_html($i->Non_unique) . '</td>';
        echo '<td>' . esc_html($i->Index_type) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

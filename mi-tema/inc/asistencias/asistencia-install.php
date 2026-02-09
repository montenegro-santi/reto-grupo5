<?php
// inc/asistencias/asistencia-install.php

if (!defined('ABSPATH')) exit;

function asistencia_tables() {
    global $wpdb;
    return [
        'empresas'         => $wpdb->prefix . 'as_empresas',
        'proyectos'        => $wpdb->prefix . 'as_proyectos',
        'tecnicos'         => $wpdb->prefix . 'as_tecnicos',
        'tecnico_empresa'  => $wpdb->prefix . 'as_tecnico_empresa',
        'tecnico_proyecto' => $wpdb->prefix . 'as_tecnico_proyecto',
    ];
}

function asistencia_install_tables() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $t = asistencia_tables();
    $charset_collate = $wpdb->get_charset_collate();

    // Nota: dbDelta es “quisquilloso”: cada campo en su línea, usar KEY (no INDEX), etc. [web:2491][web:2619][web:2642]

    $sql_empresas = "CREATE TABLE {$t['empresas']} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        nombre VARCHAR(191) NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY nombre (nombre)
    ) $charset_collate;";

    $sql_proyectos = "CREATE TABLE {$t['proyectos']} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        empresa_id BIGINT(20) UNSIGNED NOT NULL,
        nombre VARCHAR(191) NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY empresa_id (empresa_id),
        KEY nombre (nombre)
    ) $charset_collate;";

    $sql_tecnicos = "CREATE TABLE {$t['tecnicos']} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        wp_user_id BIGINT(20) UNSIGNED NULL,
        nombre VARCHAR(191) NOT NULL,
        nivel TINYINT(1) NOT NULL,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY nivel (nivel),
        KEY wp_user_id (wp_user_id),
        KEY nombre (nombre)
    ) $charset_collate;";

    $sql_tecnico_empresa = "CREATE TABLE {$t['tecnico_empresa']} (
        tecnico_id BIGINT(20) UNSIGNED NOT NULL,
        empresa_id BIGINT(20) UNSIGNED NOT NULL,
        PRIMARY KEY  (tecnico_id, empresa_id),
        KEY empresa_id (empresa_id)
    ) $charset_collate;";

    $sql_tecnico_proyecto = "CREATE TABLE {$t['tecnico_proyecto']} (
        tecnico_id BIGINT(20) UNSIGNED NOT NULL,
        proyecto_id BIGINT(20) UNSIGNED NOT NULL,
        PRIMARY KEY  (tecnico_id, proyecto_id),
        KEY proyecto_id (proyecto_id)
    ) $charset_collate;";

    dbDelta($sql_empresas);
    dbDelta($sql_proyectos);
    dbDelta($sql_tecnicos);
    dbDelta($sql_tecnico_empresa);
    dbDelta($sql_tecnico_proyecto);

    update_option('asistencia_schema_version', '1.0');

    return $wpdb->last_error; // vacío si OK
}

// 1) Instalación automática al activar/cambiar el tema (solo se ejecuta al cambiar tema). [web:2642]
add_action('after_switch_theme', function () {
    asistencia_install_tables();
});

// 2) Instalación manual desde wp-admin (para no depender de cambiar tema)
add_action('admin_post_asistencia_install', function () {
    if (!current_user_can('manage_options')) wp_die('Sin permisos.');
    check_admin_referer('asistencia_install_now');

    $err = asistencia_install_tables();

    $url = add_query_arg([
        'page' => 'asistencia_buscar',
        'as_installed' => $err ? '0' : '1',
        'as_error' => $err ? rawurlencode($err) : '',
    ], admin_url('admin.php'));

    wp_safe_redirect($url);
    exit;
});

<?php
if (!defined('ABSPATH')) exit;

function seidor_create_table_diseno_web() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'seidor_diseno_web';
  $charset_collate = $wpdb->get_charset_collate();

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

  $sql = "CREATE TABLE $table_name (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    nombre VARCHAR(120) NOT NULL,
    apellidos VARCHAR(180) NOT NULL,
    email VARCHAR(190) NOT NULL,
    telefono VARCHAR(40) NOT NULL,
    web VARCHAR(255) NULL,
    tipo VARCHAR(40) NOT NULL,
    detalle LONGTEXT NOT NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    PRIMARY KEY (id),
    KEY created_at (created_at),
    KEY email (email)
  ) $charset_collate;";

  dbDelta($sql);
}

function seidor_create_table_incidencias() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'seidor_incidencias';
  $charset_collate = $wpdb->get_charset_collate();

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

  $sql = "CREATE TABLE $table_name (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    nombre VARCHAR(120) NOT NULL,
    apellidos VARCHAR(180) NOT NULL,
    email VARCHAR(190) NOT NULL,
    telefono VARCHAR(40) NOT NULL,

    asunto VARCHAR(200) NOT NULL,
    servicio VARCHAR(40) NOT NULL,
    categoria VARCHAR(40) NOT NULL,
    impacto VARCHAR(40) NOT NULL,
    urgencia VARCHAR(40) NOT NULL,

    url VARCHAR(255) NULL,
    inicio DATETIME NULL,
    entorno VARCHAR(30) NOT NULL,

    descripcion LONGTEXT NOT NULL,
    evidencias LONGTEXT NULL,

    estado VARCHAR(20) NOT NULL DEFAULT 'abierta',
    assigned_to BIGINT(20) UNSIGNED NULL,
    assigned_at DATETIME NULL,

    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,

    PRIMARY KEY (id),
    KEY created_at (created_at),
    KEY estado (estado),
    KEY email (email),
    KEY assigned_to (assigned_to)
  ) $charset_collate;";

  dbDelta($sql);
}

function seidor_migrate_incidencias_table() {
  global $wpdb;
  $table = $wpdb->prefix . 'seidor_incidencias';

  $cols = $wpdb->get_col("SHOW COLUMNS FROM $table", 0);
  if (!is_array($cols)) return;

  if (!in_array('estado', $cols, true)) {
    $wpdb->query("ALTER TABLE $table ADD COLUMN estado VARCHAR(20) NOT NULL DEFAULT 'abierta'");
  }
  if (!in_array('assigned_to', $cols, true)) {
    $wpdb->query("ALTER TABLE $table ADD COLUMN assigned_to BIGINT(20) UNSIGNED NULL AFTER estado");
  }
  if (!in_array('assigned_at', $cols, true)) {
    $wpdb->query("ALTER TABLE $table ADD COLUMN assigned_at DATETIME NULL AFTER assigned_to");
  }
  if (!in_array('solved_at', $cols, true)) {
    $wpdb->query("ALTER TABLE $table ADD COLUMN solved_at DATETIME NULL AFTER assigned_at");
  }
  if (!in_array('solved_by', $cols, true)) {
    $wpdb->query("ALTER TABLE $table ADD COLUMN solved_by BIGINT(20) UNSIGNED NULL AFTER solved_at");
  }
}

add_action('admin_init', function () {
  global $wpdb;

  $t1 = $wpdb->prefix . 'seidor_diseno_web';
  if (!seidor_table_exists($t1)) {
    seidor_create_table_diseno_web();
  }

  $t2 = $wpdb->prefix . 'seidor_incidencias';
  if (!seidor_table_exists($t2)) {
    seidor_create_table_incidencias();
  } else {
    seidor_migrate_incidencias_table();
  }
});

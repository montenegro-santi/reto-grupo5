<?php
if (!defined('ABSPATH')) exit;

add_action('admin_post_seidor_diseno_web', 'seidor_handle_diseno_web');
add_action('admin_post_nopriv_seidor_diseno_web', 'seidor_handle_diseno_web');

function seidor_handle_diseno_web() {
  if (!isset($_POST['seidor_nonce']) || !wp_verify_nonce($_POST['seidor_nonce'], 'seidor_diseno_web')) {
    wp_die('Nonce inválido.');
  }

  $nombre    = sanitize_text_field($_POST['nombre'] ?? '');
  $apellidos = sanitize_text_field($_POST['apellidos'] ?? '');
  $email     = sanitize_email($_POST['email'] ?? '');
  $telefono  = sanitize_text_field($_POST['telefono'] ?? '');
  $web       = esc_url_raw($_POST['web'] ?? '');
  $tipo      = sanitize_text_field($_POST['tipo'] ?? '');
  $detalle   = sanitize_textarea_field($_POST['detalle'] ?? '');

  global $wpdb;
  $table = $wpdb->prefix . 'seidor_diseno_web';

  $wpdb->insert($table, [
    'nombre'     => $nombre,
    'apellidos'  => $apellidos,
    'email'      => $email,
    'telefono'   => $telefono,
    'web'        => $web ?: null,
    'tipo'       => $tipo,
    'detalle'    => $detalle,
    'ip'         => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
    'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
  ]);

  wp_safe_redirect(home_url('/solicitar-diseno-web/?enviado=1'));
  exit;
}

add_action('admin_menu', function () {
  add_menu_page(
    'Solicitudes diseño web',
    'Seidor Solicitudes',
    'manage_options',
    'seidor-solicitudes',
    'seidor_render_solicitudes_page',
    'dashicons-list-view',
    26
  );
});

function seidor_render_solicitudes_page() {
  if (!current_user_can('manage_options')) wp_die('No tienes permisos.');

  global $wpdb;
  $table = $wpdb->prefix . 'seidor_diseno_web';
  $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 200");

  echo '<div class="wrap"><h1>Solicitudes de diseño web</h1>';

  if (empty($rows)) {
    echo '<p>No hay registros.</p></div>';
    return;
  }

  echo '<table class="widefat fixed striped"><thead><tr>
    <th>ID</th><th>Fecha</th><th>Nombre</th><th>Apellidos</th><th>Email</th>
    <th>Teléfono</th><th>Web</th><th>Tipo</th><th>Detalle</th>
  </tr></thead><tbody>';

  foreach ($rows as $r) {
    echo '<tr>';
    echo '<td>' . esc_html($r->id) . '</td>';
    echo '<td>' . esc_html($r->created_at) . '</td>';
    echo '<td>' . esc_html($r->nombre) . '</td>';
    echo '<td>' . esc_html($r->apellidos) . '</td>';
    echo '<td>' . esc_html($r->email) . '</td>';
    echo '<td>' . esc_html($r->telefono) . '</td>';
    echo '<td>' . esc_html($r->web) . '</td>';
    echo '<td>' . esc_html($r->tipo) . '</td>';
    echo '<td style="max-width:520px;white-space:normal;">' . esc_html($r->detalle) . '</td>';
    echo '</tr>';
  }

  echo '</tbody></table></div>';
}

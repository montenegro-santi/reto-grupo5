<?php
if (!defined('ABSPATH')) exit;

/**
 * =========
 * Helpers
 * =========
 */

function seidor_current_url(): string {
  $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
  return esc_url_raw(home_url($request_uri));
}

function seidor_vc_secret(): string {
  return wp_salt('seidor_videollamada');
}

function seidor_vc_hmac(string $data): string {
  return hash_hmac('sha256', $data, seidor_vc_secret());
}

/**
 * Link firmado: ticket|room|uid|exp
 */
function seidor_videollamada_make_link(int $ticket_id, int $user_id, string $room, int $ttl_seconds = 3600): string {
  $ticket_id = (int) $ticket_id;
  $user_id   = (int) $user_id;
  $room      = sanitize_key($room);
  $exp       = time() + max(60, (int) $ttl_seconds);

  $data = $ticket_id . '|' . $room . '|' . $user_id . '|' . $exp;
  $sig  = seidor_vc_hmac($data);

  return add_query_arg([
    'ticket' => $ticket_id,
    'room'   => $room,
    'uid'    => $user_id,
    'exp'    => $exp,
    'sig'    => $sig,
  ], home_url('/videollamada/'));
}

/**
 * Sala activa por usuario (para redirección automática).
 */
function seidor_vc_set_active_invite(int $user_id, int $ticket_id, string $room, int $ttl_seconds = 3600): void {
  $room = sanitize_key($room);
  $exp  = time() + max(60, (int) $ttl_seconds);

  update_user_meta($user_id, 'seidor_vc_invite', [
    'ticket' => (int) $ticket_id,
    'room'   => $room,
    'exp'    => (int) $exp,
  ]);
}

function seidor_vc_get_active_invite(int $user_id): array {
  $invite = get_user_meta($user_id, 'seidor_vc_invite', true);
  return is_array($invite) ? $invite : [];
}

/**
 * =========
 * DB (requests)
 * =========
 */

function seidor_vc_requests_table(): string {
  global $wpdb;
  return $wpdb->prefix . 'seidor_vc_requests';
}

function seidor_vc_install_table(): void {
  global $wpdb;
  $table   = seidor_vc_requests_table();
  $charset = $wpdb->get_charset_collate();

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';

  $sql = "CREATE TABLE $table (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    ticket_id BIGINT UNSIGNED NULL,
    reason TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    approved_at DATETIME NULL,
    approved_by BIGINT UNSIGNED NULL,
    room VARCHAR(191) NULL,
    exp INT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY status (status),
    KEY user_id (user_id),
    KEY ticket_id (ticket_id)
  ) $charset;";

  dbDelta($sql);
  update_option('seidor_vc_db_version', '1');
}

/**
 * Si no existe la tabla, la crea automáticamente al entrar un admin.
 */
add_action('init', function () {
  if (!is_admin() || !current_user_can('manage_options')) return;

  global $wpdb;
  $table = seidor_vc_requests_table();

  $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
  if ($exists !== $table) {
    seidor_vc_install_table();
  }
});

/**
 * =========
 * Seguridad (login + validación links)
 * =========
 */
add_action('template_redirect', function () {
  if (function_exists('wp_doing_ajax') && wp_doing_ajax()) return;
  if (defined('REST_REQUEST') && REST_REQUEST) return;

  $protected_slugs = ['incidencias', 'videollamada'];

  $is_protected = false;
  foreach ($protected_slugs as $slug) {
    if (is_page($slug)) { $is_protected = true; break; }
  }
  if (!$is_protected) return;

  if (!is_user_logged_in()) {
    $login_url = wp_login_url(seidor_current_url());
    wp_safe_redirect($login_url);
    exit;
  }

  if (is_page('videollamada')) {

    // Admins pasan siempre
    if (current_user_can('manage_options')) return;

    // Si viene con parámetros, validar enlace firmado
    $has_params = isset($_GET['ticket'], $_GET['room'], $_GET['uid'], $_GET['exp'], $_GET['sig']);

    if ($has_params) {
      $ticket = (int) ($_GET['ticket'] ?? 0);
      $room   = sanitize_key($_GET['room'] ?? '');
      $uid    = (int) ($_GET['uid'] ?? 0);
      $exp    = (int) ($_GET['exp'] ?? 0);
      $sig    = sanitize_text_field($_GET['sig'] ?? '');

      if ($ticket <= 0 || $room === '' || $uid <= 0 || $exp <= 0 || $sig === '') wp_die('Enlace inválido.');
      if (get_current_user_id() !== $uid) wp_die('Este enlace no es para tu usuario.');
      if (time() > $exp) wp_die('Enlace caducado.');

      $data     = $ticket . '|' . $room . '|' . $uid . '|' . $exp;
      $expected = seidor_vc_hmac($data);

      if (!hash_equals($expected, $sig)) wp_die('Enlace no válido.');

      return;
    }

    // Si NO viene con parámetros: si tiene invite activo, redirigir al link firmado
    $invite     = seidor_vc_get_active_invite(get_current_user_id());
    $has_active = !empty($invite['ticket']) && !empty($invite['room']) && !empty($invite['exp']) && time() <= (int)$invite['exp'];

    if ($has_active) {
      $ttl   = max(60, (int)$invite['exp'] - time());
      $signed = seidor_videollamada_make_link((int)$invite['ticket'], get_current_user_id(), (string)$invite['room'], $ttl);
      wp_safe_redirect($signed);
      exit;
    }
  }

}, 0);

/**
 * =========
 * Front: Crear solicitud (cliente)
 * =========
 * Hook admin_post_{action}: admin-post.php [web:2125]
 */
add_action('admin_post_seidor_vc_request_create', function () {

  if (!is_user_logged_in()) wp_die('Login requerido.');
  if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'seidor_vc_request_create')) wp_die('Nonce inválido.');

  $ticket_id = isset($_POST['ticket_id']) ? (int) $_POST['ticket_id'] : 0;
  $reason    = sanitize_textarea_field($_POST['reason'] ?? '');

  global $wpdb;
  $table = seidor_vc_requests_table();

  // Asegurar tabla por si acaso
  $exists_table = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
  if ($exists_table !== $table) {
    seidor_vc_install_table();
  }

  // Evitar duplicados (1 pendiente por usuario)
  $exists = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table WHERE user_id=%d AND status='pending'",
    get_current_user_id()
  ));

  if (!$exists) {
    $ok = $wpdb->insert($table, [
      'user_id'    => get_current_user_id(),
      'ticket_id'  => $ticket_id ?: null,
      'reason'     => $reason ?: null,
      'status'     => 'pending',
      'created_at' => current_time('mysql'),
    ]);

    if (!$ok) {
      wp_die('Error guardando solicitud: ' . esc_html($wpdb->last_error));
    }
  }

  wp_safe_redirect(add_query_arg('vc', 'requested', home_url('/videollamada/')));
  exit;
});

/**
 * =========
 * Admin: Panel + Aprobar/Rechazar/Cerrar
 * =========
 */
add_action('admin_menu', function () {
  add_submenu_page(
    'seidor-incidencias-abiertas',
    'Solicitudes Videollamada',
    'Solicitudes VC',
    'manage_options',
    'seidor-vc-requests',
    'seidor_vc_admin_page'
  );
});

function seidor_vc_admin_page() {
  if (!current_user_can('manage_options')) wp_die('No tienes permisos.');

  global $wpdb;
  $table = seidor_vc_requests_table();

  $pending  = $wpdb->get_results("SELECT * FROM $table WHERE status='pending' ORDER BY created_at DESC LIMIT 200");
  $approved = $wpdb->get_results("SELECT * FROM $table WHERE status='approved' ORDER BY approved_at DESC LIMIT 50");

  echo '<div class="wrap"><h1>Solicitudes de Videollamada</h1>';

  // Notices
  if (isset($_GET['msg']) && $_GET['msg'] === 'approved' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $r = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $id));
    if ($r && $r->status === 'approved') {
      $ttl = max(60, (int)$r->exp - time());
      $client_link = seidor_videollamada_make_link((int)$r->ticket_id, (int)$r->user_id, (string)$r->room, $ttl);
      $tech_link   = seidor_videollamada_make_link((int)$r->ticket_id, get_current_user_id(), (string)$r->room, $ttl);

      echo '<div class="notice notice-success"><p><strong>✓ Aprobada.</strong> ';
      echo 'Link cliente: <a href="' . esc_url($client_link) . '" target="_blank" rel="noopener">Abrir</a> · ';
      echo 'Link técnico: <a href="' . esc_url($tech_link) . '" target="_blank" rel="noopener">Abrir</a></p></div>';
    }
  }

  if (isset($_GET['msg']) && $_GET['msg'] === 'rejected') {
    echo '<div class="notice notice-warning is-dismissible"><p>✓ Solicitud rechazada.</p></div>';
  }

  if (isset($_GET['msg']) && $_GET['msg'] === 'closed') {
    echo '<div class="notice notice-success is-dismissible"><p>✓ Videollamada cerrada (acceso revocado).</p></div>';
  }

  // Pendientes
  echo '<h2>Pendientes</h2>';

  if (!$pending) {
    echo '<p>No hay solicitudes pendientes.</p>';
  } else {
    echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Usuario</th><th>Ticket</th><th>Motivo</th><th>Fecha</th><th>Acciones</th></tr></thead><tbody>';

    foreach ($pending as $r) {
      $u = get_user_by('id', (int)$r->user_id);
      $uname = $u ? esc_html($u->display_name . ' (' . $u->user_email . ')') : ('ID ' . (int)$r->user_id);

      $approve_nonce = wp_create_nonce('seidor_vc_approve_' . (int)$r->id);
      $reject_nonce  = wp_create_nonce('seidor_vc_reject_' . (int)$r->id);

      echo '<tr>';
      echo '<td>' . (int)$r->id . '</td>';
      echo '<td>' . $uname . '</td>';
      echo '<td>' . esc_html($r->ticket_id ?: '—') . '</td>';
      echo '<td>' . esc_html($r->reason ?: '—') . '</td>';
      echo '<td>' . esc_html($r->created_at) . '</td>';
      echo '<td style="white-space:nowrap;">';

      echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin-right:6px;">';
      echo '<input type="hidden" name="action" value="seidor_vc_approve">';
      echo '<input type="hidden" name="id" value="' . (int)$r->id . '">';
      echo '<input type="hidden" name="_wpnonce" value="' . esc_attr($approve_nonce) . '">';
      echo '<button class="button button-primary" type="submit">Aprobar</button>';
      echo '</form>';

      echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;">';
      echo '<input type="hidden" name="action" value="seidor_vc_reject">';
      echo '<input type="hidden" name="id" value="' . (int)$r->id . '">';
      echo '<input type="hidden" name="_wpnonce" value="' . esc_attr($reject_nonce) . '">';
      echo '<button class="button" type="submit">Rechazar</button>';
      echo '</form>';

      echo '</td>';
      echo '</tr>';
    }

    echo '</tbody></table>';
  }

  // Aprobadas
  echo '<h2>Últimas aprobadas (links)</h2>';
  if (!$approved) {
    echo '<p>No hay aprobadas recientes.</p>';
  } else {
    echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Usuario</th><th>Sala</th><th>Link cliente</th><th>Link técnico</th><th>Caduca</th><th>Acciones</th></tr></thead><tbody>';

    foreach ($approved as $r) {
      $u = get_user_by('id', (int)$r->user_id);
      $uname = $u ? esc_html($u->display_name) : ('ID ' . (int)$r->user_id);

      $ttl = max(60, (int)$r->exp - time());
      $client_link = seidor_videollamada_make_link((int)$r->ticket_id, (int)$r->user_id, (string)$r->room, $ttl);
      $tech_link   = seidor_videollamada_make_link((int)$r->ticket_id, get_current_user_id(), (string)$r->room, $ttl);

      $close_nonce = wp_create_nonce('seidor_vc_close_' . (int)$r->id);

      echo '<tr>';
      echo '<td>' . (int)$r->id . '</td>';
      echo '<td>' . $uname . '</td>';
      echo '<td><code>' . esc_html($r->room) . '</code></td>';
      echo '<td><a href="' . esc_url($client_link) . '" target="_blank" rel="noopener">Abrir</a></td>';
      echo '<td><a href="' . esc_url($tech_link) . '" target="_blank" rel="noopener">Abrir</a></td>';
      echo '<td>' . esc_html(date('Y-m-d H:i:s', (int)$r->exp)) . '</td>';

      // Botón Cerrar
      echo '<td style="white-space:nowrap;">';
      echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
      echo '<input type="hidden" name="action" value="seidor_vc_close">';
      echo '<input type="hidden" name="id" value="' . (int)$r->id . '">';
      echo '<input type="hidden" name="_wpnonce" value="' . esc_attr($close_nonce) . '">';
      echo '<button class="button" type="submit">Cerrar videollamada</button>';
      echo '</form>';
      echo '</td>';

      echo '</tr>';
    }

    echo '</tbody></table>';
  }

  echo '</div>';
}

/**
 * Aprobar solicitud (técnico/admin).
 */
add_action('admin_post_seidor_vc_approve', function () {
  if (!current_user_can('manage_options')) wp_die('No tienes permisos.');

  $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
  if ($id <= 0) wp_die('ID inválido.');
  if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'seidor_vc_approve_' . $id)) wp_die('Nonce inválido.');

  global $wpdb;
  $table = seidor_vc_requests_table();

  $req = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $id));
  if (!$req || $req->status !== 'pending') {
    wp_safe_redirect(admin_url('admin.php?page=seidor-vc-requests'));
    exit;
  }

  $ticket_id = (int) ($req->ticket_id ?: $req->id);
  $room = 'inc-' . $ticket_id . '-' . sanitize_key(wp_generate_password(10, false, false));
  $ttl = 3600;
  $exp = time() + $ttl;

  $wpdb->update($table, [
    'status'      => 'approved',
    'approved_at' => current_time('mysql'),
    'approved_by' => get_current_user_id(),
    'room'        => $room,
    'exp'         => $exp,
    'ticket_id'   => $ticket_id,
  ], ['id' => $id]);

  // Asigna sala activa al cliente (redirige automáticamente en /videollamada/)
  seidor_vc_set_active_invite((int)$req->user_id, $ticket_id, $room, $ttl);

  // (Opcional) Email al cliente con link
  $u = get_user_by('id', (int)$req->user_id);
  if ($u) {
    $client_link = seidor_videollamada_make_link($ticket_id, (int)$req->user_id, $room, $ttl);
    $subject = 'Acceso a videollamada habilitado';
    $message = "Tu acceso a videollamada ha sido habilitado.\n\nEnlace:\n$client_link\n\nEste enlace caduca en 1 hora.\n";
    wp_mail($u->user_email, $subject, $message, ['Content-Type: text/plain; charset=UTF-8']);
  }

  wp_safe_redirect(admin_url('admin.php?page=seidor-vc-requests&msg=approved&id=' . $id));
  exit;
});

/**
 * Rechazar solicitud.
 */
add_action('admin_post_seidor_vc_reject', function () {
  if (!current_user_can('manage_options')) wp_die('No tienes permisos.');

  $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
  if ($id <= 0) wp_die('ID inválido.');
  if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'seidor_vc_reject_' . $id)) wp_die('Nonce inválido.');

  global $wpdb;
  $table = seidor_vc_requests_table();

  $req = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $id));
  if ($req && $req->status === 'pending') {
    $wpdb->update($table, ['status' => 'rejected'], ['id' => $id]);
  }

  wp_safe_redirect(admin_url('admin.php?page=seidor-vc-requests&msg=rejected'));
  exit;
});

/**
 * AJAX: comprobar si el usuario ya tiene una sala activa y devolver el link firmado
 */
add_action('wp_ajax_seidor_vc_check', function () {

  if (!is_user_logged_in()) {
    wp_send_json_error(['message' => 'Login requerido.'], 401);
  }

  $invite = seidor_vc_get_active_invite(get_current_user_id());
  $has_active = !empty($invite['ticket']) && !empty($invite['room']) && !empty($invite['exp']) && time() <= (int)$invite['exp'];

  if (!$has_active) {
    wp_send_json_success(['ready' => false]);
  }

  $ttl = max(60, (int)$invite['exp'] - time());
  $url = seidor_videollamada_make_link((int)$invite['ticket'], get_current_user_id(), (string)$invite['room'], $ttl);

  wp_send_json_success(['ready' => true, 'url' => $url]);
});

/**
 * Cerrar videollamada (revoca acceso + marca como closed)
 * Se ejecuta desde un form en el panel con admin_post_{action}. [web:2125]
 */
add_action('admin_post_seidor_vc_close', function () {
  if (!current_user_can('manage_options')) wp_die('No tienes permisos.');

  $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
  if ($id <= 0) wp_die('ID inválido.');

  if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'seidor_vc_close_' . $id)) {
    wp_die('Nonce inválido.');
  }

  global $wpdb;
  $table = seidor_vc_requests_table();

  $req = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $id));
  if (!$req) {
    wp_safe_redirect(admin_url('admin.php?page=seidor-vc-requests'));
    exit;
  }

  // Revocar sala activa del cliente. [web:2246]
  delete_user_meta((int)$req->user_id, 'seidor_vc_invite');

  // Marcar en histórico
  $wpdb->update($table, ['status' => 'closed'], ['id' => $id]);

  wp_safe_redirect(admin_url('admin.php?page=seidor-vc-requests&msg=closed'));
  exit;
});

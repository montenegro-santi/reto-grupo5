<?php
if (!defined('ABSPATH')) exit;


/**
 * Enviar email de confirmación al usuario (incidencia).
 * Usa wp_mail() con headers en array.
 */
function seidor_send_confirm_email_incidencia($to_email, $to_name, $incidencia_id) {
  $to_email = sanitize_email($to_email);
  if (!$to_email) return false;

  $to_name = sanitize_text_field($to_name);
  $incidencia_id = (int) $incidencia_id;

  $subject = 'Confirmación: incidencia recibida';
  $message = "Hola $to_name,\n\nHemos recibido tu incidencia correctamente.\nID: $incidencia_id\n\nGracias.";
  $headers = ['Content-Type: text/plain; charset=UTF-8'];

  return wp_mail($to_email, $subject, $message, $headers);
}


/* Crear incidencia (front) */
add_action('admin_post_seidor_incidencia', 'seidor_handle_incidencia');
add_action('admin_post_nopriv_seidor_incidencia', 'seidor_handle_incidencia');

function seidor_handle_incidencia() {
  if (!isset($_POST['seidor_nonce']) || !wp_verify_nonce($_POST['seidor_nonce'], 'seidor_incidencia')) {
    wp_die('Nonce inválido.');
  }

  $nombre     = sanitize_text_field($_POST['nombre'] ?? '');
  $apellidos  = sanitize_text_field($_POST['apellidos'] ?? '');
  $email      = sanitize_email($_POST['email'] ?? '');
  $telefono   = sanitize_text_field($_POST['telefono'] ?? '');

  $asunto     = sanitize_text_field($_POST['asunto'] ?? '');
  $servicio   = sanitize_text_field($_POST['servicio'] ?? '');
  $categoria  = sanitize_text_field($_POST['categoria'] ?? '');
  $impacto    = sanitize_text_field($_POST['impacto'] ?? '');
  $urgencia   = sanitize_text_field($_POST['urgencia'] ?? '');

  $url         = esc_url_raw($_POST['url'] ?? '');
  $entorno     = sanitize_text_field($_POST['entorno'] ?? '');
  $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
  $evidencias  = sanitize_textarea_field($_POST['evidencias'] ?? '');

  $inicio_raw  = sanitize_text_field($_POST['inicio'] ?? '');
  $inicio      = $inicio_raw ? str_replace('T', ' ', $inicio_raw) . ':00' : null;

  global $wpdb;
  $table = $wpdb->prefix . 'seidor_incidencias';

  $wpdb->insert($table, [
    'nombre'      => $nombre,
    'apellidos'   => $apellidos,
    'email'       => $email,
    'telefono'    => $telefono,
    'asunto'      => $asunto,
    'servicio'    => $servicio,
    'categoria'   => $categoria,
    'impacto'     => $impacto,
    'urgencia'    => $urgencia,
    'url'         => $url ?: null,
    'inicio'      => $inicio,
    'entorno'     => $entorno,
    'descripcion' => $descripcion,
    'evidencias'  => $evidencias ?: null,
    'estado'      => 'abierta',
    'ip'          => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
    'user_agent'  => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
  ]);

  // Enviar confirmación al usuario (si insertó OK)
  $incidencia_id = (int) $wpdb->insert_id;
  seidor_send_confirm_email_incidencia($email, $nombre, $incidencia_id);

  wp_safe_redirect(home_url('/incidencias/?enviado=1'));
  exit;
}


/* Asignarme */
add_action('admin_post_seidor_incidencia_asignarme', function () {
  if (!current_user_can('manage_options')) wp_die('No tienes permisos.');

  $id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
  if ($id <= 0) wp_die('ID inválido.');

  if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'seidor_asignar_' . $id)) {
    wp_die('Nonce inválido.');
  }

  global $wpdb;
  $table = $wpdb->prefix . 'seidor_incidencias';

  $row = $wpdb->get_row($wpdb->prepare("SELECT id, estado, assigned_to FROM $table WHERE id = %d", $id));
  if (!$row) wp_die('Incidencia no encontrada.');

  if ($row->estado !== 'abierta' || !empty($row->assigned_to)) {
    wp_safe_redirect(admin_url('admin.php?page=seidor-incidencias-abiertas'));
    exit;
  }

  $wpdb->update(
    $table,
    [
      'assigned_to' => get_current_user_id(),
      'assigned_at' => current_time('mysql'),
      'estado'      => 'en_progreso',
    ],
    ['id' => $id]
  );

  wp_safe_redirect(admin_url('admin.php?page=seidor-incidencias-asignadas&msg=asignada'));
  exit;
});


/* Liberar */
add_action('admin_post_seidor_incidencia_liberar', function () {
  if (!current_user_can('manage_options')) wp_die('No tienes permisos.');

  $id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
  if ($id <= 0) wp_die('ID inválido.');

  if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'seidor_liberar_' . $id)) {
    wp_die('Nonce inválido.');
  }

  global $wpdb;
  $table = $wpdb->prefix . 'seidor_incidencias';

  $wpdb->update(
    $table,
    [
      'assigned_to' => null,
      'assigned_at' => null,
      'estado'      => 'abierta',
    ],
    ['id' => $id]
  );

  wp_safe_redirect(admin_url('admin.php?page=seidor-incidencias-abiertas&msg=liberada'));
  exit;
});


/* Solucionar */
add_action('admin_post_seidor_incidencia_solucionar', function () {
  if (!current_user_can('manage_options')) wp_die('No tienes permisos.');

  $id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
  if ($id <= 0) wp_die('ID inválido.');

  if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'seidor_solucionar_' . $id)) {
    wp_die('Nonce inválido.');
  }

  global $wpdb;
  $table = $wpdb->prefix . 'seidor_incidencias';

  $wpdb->update(
    $table,
    [
      'estado'    => 'solucionada',
      'solved_at' => current_time('mysql'),
      'solved_by' => get_current_user_id(),
    ],
    ['id' => $id]
  );

  wp_safe_redirect(admin_url('admin.php?page=seidor-incidencias-solucionadas&msg=solucionada'));
  exit;
});


/* Admin menu + 3 submenús */
add_action('admin_menu', function () {
  add_menu_page(
    'Seidor Incidencias',
    'Seidor Incidencias',
    'manage_options',
    'seidor-incidencias-abiertas',
    'seidor_render_incidencias_abiertas_page',
    'dashicons-sos',
    26
  );

  add_submenu_page(
    'seidor-incidencias-abiertas',
    'Incidencias abiertas',
    'Abiertas',
    'manage_options',
    'seidor-incidencias-abiertas',
    'seidor_render_incidencias_abiertas_page'
  );

  add_submenu_page(
    'seidor-incidencias-abiertas',
    'Incidencias asignadas',
    'Asignadas',
    'manage_options',
    'seidor-incidencias-asignadas',
    'seidor_render_incidencias_asignadas_page'
  );

  add_submenu_page(
    'seidor-incidencias-abiertas',
    'Incidencias solucionadas',
    'Solucionadas',
    'manage_options',
    'seidor-incidencias-solucionadas',
    'seidor_render_incidencias_solucionadas_page'
  );
});


/* Enqueue CSS para mejorar botones */
add_action('admin_enqueue_scripts', function() {
  $css = '
    /* Contenedor de botones mejorado */
    .seidor-incidencias-actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items: center;
      width: 100%;
    }
    
    .seidor-incidencias-actions form {
      display: inline-flex;
      flex: 0 1 auto;
    }
    
    .seidor-incidencias-actions .button {
      white-space: nowrap;
      padding: 6px 14px;
      font-size: 13px;
      line-height: 1.5;
      min-width: 80px;
    }
    
    /* Responsive: en pantallas pequeñas, botones en fila con scroll */
    @media (max-width: 1024px) {
      .seidor-incidencias-actions {
        gap: 5px;
        justify-content: flex-start;
      }
      
      .seidor-incidencias-actions .button {
        padding: 5px 12px;
        font-size: 12px;
        min-width: 75px;
      }
    }
    
    /* En móvil, botones más pequeños pero visibles */
    @media (max-width: 640px) {
      .seidor-incidencias-actions {
        gap: 4px;
      }
      
      .seidor-incidencias-actions .button {
        padding: 4px 10px;
        font-size: 11px;
        min-width: 70px;
      }
    }
    
    /* Tabla responsive */
    .seidor-incidencias-table {
      overflow-x: auto;
      display: block;
    }
    
    @media (max-width: 768px) {
      .seidor-incidencias-table table {
        font-size: 12px;
      }
      
      .seidor-incidencias-table th,
      .seidor-incidencias-table td {
        padding: 8px 4px !important;
      }
    }
  ';
  wp_add_inline_style('wp-admin', $css);
});


/* Función auxiliar para pintar tabla */
function seidor_render_incidencias_table($rows, $tab = 'abiertas') {
  if (empty($rows)) {
    echo '<p>No hay incidencias en esta sección.</p>';
    return;
  }

  echo '<div class="seidor-incidencias-table">';
  echo '<table class="widefat fixed striped"><thead><tr>
    <th>ID</th><th>Fecha</th><th>Estado</th><th>Asunto</th><th>Prioridad</th>
    <th>Solicitante</th><th>Email</th><th>Teléfono</th><th>Servicio</th>
    <th>Categoría</th><th>Entorno</th>';
  
  if ($tab === 'asignadas') {
    echo '<th>Asignado a</th><th>Desde</th>';
  } elseif ($tab === 'solucionadas') {
    echo '<th>Asignado a</th><th>Solucionado por</th><th>Resuelto</th>';
  }
  
  echo '<th>Acción</th></tr></thead><tbody>';

  foreach ($rows as $r) {
    $prioridad = esc_html($r->impacto . ' / ' . $r->urgencia);
    $solicitante = esc_html($r->nombre . ' ' . $r->apellidos);

    $assigned_name = '—';
    if (!empty($r->assigned_to)) {
      $u = get_user_by('id', (int)$r->assigned_to);
      $assigned_name = $u ? esc_html($u->display_name) : ('ID ' . (int)$r->assigned_to);
    }

    echo '<tr>';
    echo '<td>' . esc_html($r->id) . '</td>';
    echo '<td>' . esc_html($r->created_at) . '</td>';
    echo '<td>' . esc_html($r->estado) . '</td>';
    echo '<td>' . esc_html($r->asunto) . '</td>';
    echo '<td>' . $prioridad . '</td>';
    echo '<td>' . $solicitante . '</td>';
    echo '<td>' . esc_html($r->email) . '</td>';
    echo '<td>' . esc_html($r->telefono) . '</td>';
    echo '<td>' . esc_html($r->servicio) . '</td>';
    echo '<td>' . esc_html($r->categoria) . '</td>';
    echo '<td>' . esc_html($r->entorno) . '</td>';

    if ($tab === 'asignadas') {
      echo '<td>' . $assigned_name . '</td>';
      echo '<td>' . esc_html($r->assigned_at) . '</td>';
    } elseif ($tab === 'solucionadas') {
      echo '<td>' . $assigned_name . '</td>';
      $solved_by = '—';
      if (!empty($r->solved_by)) {
        $u = get_user_by('id', (int)$r->solved_by);
        $solved_by = $u ? esc_html($u->display_name) : ('ID ' . (int)$r->solved_by);
      }
      echo '<td>' . $solved_by . '</td>';
      echo '<td>' . esc_html($r->solved_at) . '</td>';
    }

    echo '<td>';

    if ($tab === 'abiertas') {
      $nonce = wp_create_nonce('seidor_asignar_' . (int)$r->id);
      echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline;">';
      echo '<input type="hidden" name="action" value="seidor_incidencia_asignarme">';
      echo '<input type="hidden" name="id" value="' . (int)$r->id . '">';
      echo '<input type="hidden" name="_wpnonce" value="' . esc_attr($nonce) . '">';
      echo '<button type="submit" class="button button-primary" style="white-space:nowrap;">Asignarme</button>';
      echo '</form>';
    } elseif ($tab === 'asignadas') {
      echo '<div class="seidor-incidencias-actions">';
      
      // Botón Solucionar
      $nonce = wp_create_nonce('seidor_solucionar_' . (int)$r->id);
      echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
      echo '<input type="hidden" name="action" value="seidor_incidencia_solucionar">';
      echo '<input type="hidden" name="id" value="' . (int)$r->id . '">';
      echo '<input type="hidden" name="_wpnonce" value="' . esc_attr($nonce) . '">';
      echo '<button type="submit" class="button button-success" style="white-space:nowrap;">Solucionar</button>';
      echo '</form>';

      // Botón Liberar
      $nonce = wp_create_nonce('seidor_liberar_' . (int)$r->id);
      echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
      echo '<input type="hidden" name="action" value="seidor_incidencia_liberar">';
      echo '<input type="hidden" name="id" value="' . (int)$r->id . '">';
      echo '<input type="hidden" name="_wpnonce" value="' . esc_attr($nonce) . '">';
      echo '<button type="submit" class="button" style="white-space:nowrap;">Liberar</button>';
      echo '</form>';

      echo '</div>';
    } else {
      echo '—';
    }

    echo '</td>';
    echo '</tr>';
  }

  echo '</tbody></table>';
  echo '</div>';
}


/* Página: Abiertas */
function seidor_render_incidencias_abiertas_page() {
  if (!current_user_can('manage_options')) wp_die('No tienes permisos.');

  global $wpdb;
  $table = $wpdb->prefix . 'seidor_incidencias';
  $rows = $wpdb->get_results("SELECT * FROM $table WHERE estado='abierta' AND (assigned_to IS NULL OR assigned_to=0) ORDER BY created_at DESC LIMIT 200");

  echo '<div class="wrap"><h1>Incidencias Abiertas</h1>';

  if (isset($_GET['msg']) && $_GET['msg'] === 'liberada') {
    echo '<div class="notice notice-info is-dismissible"><p>✓ Incidencia liberada.</p></div>';
  }

  seidor_render_incidencias_table($rows, 'abiertas');
  echo '</div>';
}


/* Página: Asignadas */
function seidor_render_incidencias_asignadas_page() {
  if (!current_user_can('manage_options')) wp_die('No tienes permisos.');

  global $wpdb;
  $table = $wpdb->prefix . 'seidor_incidencias';
  $rows = $wpdb->get_results("SELECT * FROM $table WHERE estado='en_progreso' AND assigned_to IS NOT NULL ORDER BY created_at DESC LIMIT 200");

  echo '<div class="wrap"><h1>Incidencias Asignadas</h1>';

  if (isset($_GET['msg']) && $_GET['msg'] === 'asignada') {
    echo '<div class="notice notice-success is-dismissible"><p>✓ Incidencia asignada correctamente.</p></div>';
  }

  seidor_render_incidencias_table($rows, 'asignadas');
  echo '</div>';
}


/* Página: Solucionadas */
function seidor_render_incidencias_solucionadas_page() {
  if (!current_user_can('manage_options')) wp_die('No tienes permisos.');

  global $wpdb;
  $table = $wpdb->prefix . 'seidor_incidencias';
  $rows = $wpdb->get_results("SELECT * FROM $table WHERE estado='solucionada' ORDER BY solved_at DESC, created_at DESC LIMIT 200");

  echo '<div class="wrap"><h1>Incidencias Solucionadas</h1>';

  if (isset($_GET['msg']) && $_GET['msg'] === 'solucionada') {
    echo '<div class="notice notice-success is-dismissible"><p>✓ Incidencia marcada como solucionada.</p></div>';
  }

  seidor_render_incidencias_table($rows, 'solucionadas');
  echo '</div>';
}

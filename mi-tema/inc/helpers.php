<?php
if (!defined('ABSPATH')) exit;

function seidor_table_exists($table) {
  global $wpdb;
  $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
  return $exists === $table;
}
function seidor_send_confirm_email($to_email, $to_name, $tipo, $id = null) {
  $to_email = sanitize_email($to_email);
  if (!$to_email) return false;

  $to_name = sanitize_text_field($to_name);
  $tipo = sanitize_text_field($tipo);

  $subject = "Confirmación: hemos recibido tu $tipo";
  $msg_id = $id ? "ID: $id\n\n" : '';

  $message = "Hola $to_name,\n\n"
    . "Hemos recibido tu $tipo correctamente.\n"
    . $msg_id
    . "Gracias.\n";

  $headers = ['Content-Type: text/plain; charset=UTF-8'];

  return wp_mail($to_email, $subject, $message, $headers);
}

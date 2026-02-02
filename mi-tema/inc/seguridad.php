<?php
if (!defined('ABSPATH')) exit;

/**
 * Páginas protegidas: si NO está logueado, redirige a login y vuelve luego.
 */
add_action('template_redirect', function () {

  // Si ya está logueado, no hacemos nada
  if (is_user_logged_in()) return;

  // Slugs de páginas a proteger (ajusta a tus URLs reales)
  $protected_slugs = ['incidencias', 'videollamada'];

  foreach ($protected_slugs as $slug) {
    if (is_page($slug)) {
      // Login de WP + return a la URL actual
      $login_url = wp_login_url( get_permalink() );
      wp_safe_redirect($login_url);
      exit;
    }
  }
});

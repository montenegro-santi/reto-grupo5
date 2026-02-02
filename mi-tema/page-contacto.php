<?php
/* page-contacto.php - /contacto/ */

$sent_ok = false;
$sent_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seidor_contact_submit'])) {
  // Nonce (seguridad)
  if (!isset($_POST['seidor_contact_nonce']) || !wp_verify_nonce($_POST['seidor_contact_nonce'], 'seidor_contact')) {
    $sent_error = 'Error de seguridad. Recarga la página y prueba de nuevo.';
  } else {
    $nombre  = sanitize_text_field($_POST['nombre'] ?? '');
    $email   = sanitize_email($_POST['email'] ?? '');
    $asunto  = sanitize_text_field($_POST['asunto'] ?? '');
    $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');

    if ($nombre === '' || $email === '' || $asunto === '' || $mensaje === '') {
      $sent_error = 'Completa todos los campos.';
    } elseif (!is_email($email)) {
      $sent_error = 'Email no válido.';
    } else {
      $to = get_option('admin_email'); // email admin del sitio
      $subject = '[Contacto] ' . $asunto;

      $body =
        "Nombre: {$nombre}\n" .
        "Email: {$email}\n" .
        "Asunto: {$asunto}\n\n" .
        "Mensaje:\n{$mensaje}\n";

      $headers = [
        'Reply-To: ' . $nombre . ' <' . $email . '>',
      ];

      // Envía correo con wp_mail()
      $sent_ok = wp_mail($to, $subject, $body, $headers); // [web:926][web:928]
      if (!$sent_ok) {
        $sent_error = 'No se pudo enviar. Revisa la configuración de correo (SMTP) del servidor.';
      }
    }
  }
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php bloginfo('name'); ?> — Contacto</title>
  <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<style>
:root{
  --bg1:#0b1220; --bg2:#0f1a31;
  --card:rgba(15,26,49,.72);
  --text:#e9eefc; --muted:#b7c3e6;
  --brand:#4f7cff; --brand2:#6a5cff;
  --border:rgba(255,255,255,.10); --border2:rgba(79,124,255,.60);
  --shadow:0 20px 70px rgba(0,0,0,.35);
  --max:1100px;
  --font: system-ui, -apple-system, "Segoe UI", Roboto, Arial, "Noto Sans", "Helvetica Neue", sans-serif;
}
*{box-sizing:border-box}
body{margin:0;font-family:var(--font);color:var(--text)}
a{color:inherit;text-decoration:none}
.wrap{
  min-height:100vh;
  background:
    radial-gradient(1200px 600px at 20% 10%, rgba(79,124,255,.25), transparent 60%),
    radial-gradient(900px 500px at 90% 20%, rgba(106,92,255,.18), transparent 60%),
    linear-gradient(180deg,var(--bg1),var(--bg2));
  display:flex; align-items:flex-start; justify-content:center;
}
.container{width:100%;max-width:var(--max);margin:0 auto;padding:44px 18px}
.shell{
  border:1px solid var(--border);
  border-radius:22px;
  background:rgba(255,255,255,.03);
  box-shadow:var(--shadow);
  overflow:hidden;
}
.topbar{
  display:flex; gap:14px; justify-content:space-between; align-items:center;
  padding:18px 20px;
  border-bottom:1px solid var(--border);
}
.brand{display:flex;gap:12px;align-items:center}
.logo{
  width:40px;height:40px;border-radius:12px;
  background:linear-gradient(135deg,var(--brand),var(--brand2));
  box-shadow:0 10px 30px rgba(79,124,255,.25);
}
.brand h1{margin:0;font-size:16px;letter-spacing:.3px}
.brand p{margin:2px 0 0;color:var(--muted);font-size:12px}
.nav{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end}
.btn{
  display:inline-flex;align-items:center;gap:10px;
  padding:10px 12px;border-radius:12px;
  border:1px solid var(--border);
  background:rgba(255,255,255,.05);
  color:var(--text); font-weight:800; font-size:13px;
  transition:.15s transform, .15s border-color, .15s background;
}
.btn:hover{transform:translateY(-1px);border-color:var(--border2);background:rgba(255,255,255,.08)}
.btn.primary{border-color:transparent;background:linear-gradient(135deg,var(--brand),var(--brand2))}
.main{padding:26px 20px 22px}
.hero h2{margin:0 0 8px;font-size:30px;letter-spacing:.2px}
.hero p{margin:0;max-width:860px;color:var(--muted);font-size:15px;line-height:1.6}

.grid{margin-top:16px;display:grid;grid-template-columns:1.2fr .8fr;gap:12px}
@media (max-width: 920px){ .grid{grid-template-columns:1fr} }

.panel{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:16px;
  padding:16px;
}
.panel h3{margin:0 0 10px;font-size:15px}
.panel p{margin:0 0 12px;color:var(--muted);font-size:13px;line-height:1.45}

.field{margin-bottom:10px}
label{display:block;font-size:12px;color:rgba(233,238,252,.85);margin:0 0 6px}
input, textarea{
  width:100%;
  padding:10px 12px;
  border-radius:12px;
  border:1px solid rgba(255,255,255,.12);
  background:rgba(255,255,255,.06);
  color:var(--text);
  outline:none;
}
textarea{min-height:130px;resize:vertical}

.notice{
  padding:10px 12px;
  border-radius:12px;
  border:1px solid rgba(255,255,255,.14);
  background:rgba(255,255,255,.06);
  color:rgba(233,238,252,.92);
  font-size:13px;
  margin-bottom:12px;
}
.notice.ok{border-color:rgba(67, 209, 152, .55)}
.notice.err{border-color:rgba(255, 107, 107, .55)}

.bottom{
  padding:14px 20px;
  border-top:1px solid var(--border);
  display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;
  color:rgba(233,238,252,.75);font-size:12px;
}
.smalllinks a{color:rgba(233,238,252,.85);text-decoration:none;border-bottom:1px dotted rgba(233,238,252,.35)}
.smalllinks a:hover{border-bottom-color:rgba(233,238,252,.85)}
.small{font-size:12px;color:rgba(233,238,252,.75);line-height:1.5;margin-top:10px}
</style>

<div class="wrap">
  <div class="container">
    <div class="shell">

      <div class="topbar">
        <div class="brand">
          <div class="logo" aria-hidden="true"></div>
          <div>
            <h1>Seidor</h1>
            <p>Panel de soporte y servicios</p>
          </div>
        </div>

        <nav class="nav">
          <a class="btn" href="<?php echo esc_url( home_url('/') ); ?>">Inicio</a>
          <a class="btn" href="<?php echo esc_url( home_url('/servicios/') ); ?>">Servicios</a>
          <a class="btn" href="<?php echo esc_url( home_url('/incidencias/') ); ?>">Incidencias</a>
          <a class="btn" href="<?php echo esc_url( home_url('/videollamada/') ); ?>">Videollamada</a>
          <a class="btn primary" href="<?php echo esc_url( home_url('/contacto/') ); ?>">Contacto</a>
        </nav>
      </div>

      <div class="main">
        <header class="hero">
          <h2>Contacto</h2>
          <p>Envíanos un mensaje y te respondemos lo antes posible.</p>
        </header>

        <section class="grid">
          <div class="panel">
            <h3>Enviar mensaje</h3>

            <?php if ($sent_ok): ?>
              <div class="notice ok">Mensaje enviado correctamente.</div>
            <?php elseif ($sent_error !== ''): ?>
              <div class="notice err"><?php echo esc_html($sent_error); ?></div>
            <?php endif; ?>

            <form method="post" action="">
              <?php wp_nonce_field('seidor_contact', 'seidor_contact_nonce'); ?>

              <div class="field">
                <label for="nombre">Nombre</label>
                <input id="nombre" name="nombre" type="text" required
                       value="<?php echo isset($_POST['nombre']) ? esc_attr($_POST['nombre']) : ''; ?>">
              </div>

              <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required
                       value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>">
              </div>

              <div class="field">
                <label for="asunto">Asunto</label>
                <input id="asunto" name="asunto" type="text" required
                       value="<?php echo isset($_POST['asunto']) ? esc_attr($_POST['asunto']) : ''; ?>">
              </div>

              <div class="field">
                <label for="mensaje">Mensaje</label>
                <textarea id="mensaje" name="mensaje" required><?php echo isset($_POST['mensaje']) ? esc_textarea($_POST['mensaje']) : ''; ?></textarea>
              </div>

              <button class="btn primary" type="submit" name="seidor_contact_submit" value="1">Enviar</button>
              <div class="small">El mensaje se envía al email de administración del sitio.</div>
            </form>
          </div>

          <aside class="panel">
            <h3>Otros canales</h3>
            <p>También puedes usar estos accesos rápidos.</p>

            <a class="btn" style="width:100%;justify-content:center;margin-bottom:10px"
               href="<?php echo esc_url( home_url('/incidencias/') ); ?>">
              Registrar incidencia
            </a>

            <a class="btn" style="width:100%;justify-content:center;margin-bottom:10px"
               href="<?php echo esc_url( home_url('/videollamada/') ); ?>">
              Ir a videollamada
            </a>

            <div class="small">
              Si el correo no llega, probablemente necesites configurar SMTP en el servidor/WordPress.
            </div>
          </aside>
        </section>
      </div>

      <div class="bottom">
        <div>© <?php echo date('Y'); ?> Seidor</div>
        <div class="smalllinks">
          <a href="<?php echo esc_url( home_url('/contacto/') ); ?>">Contacto</a> ·
          <a href="<?php echo esc_url( home_url('/politica-privacidad/') ); ?>">Privacidad</a>
        </div>
      </div>

    </div>
  </div>
</div>

<?php wp_footer(); ?>
</body>
</html>

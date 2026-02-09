<?php
/* page-videollamada.php - /videollamada/ */
if (!defined('ABSPATH')) exit;

$room   = isset($_GET['room']) ? sanitize_key($_GET['room']) : '';
$ticket = isset($_GET['ticket']) ? (int) $_GET['ticket'] : 0;

$requested = (isset($_GET['vc']) && $_GET['vc'] === 'requested');

// Invite activo (por si el template_redirect todavía no redirigió por cualquier motivo)
$invite = function_exists('seidor_vc_get_active_invite') ? seidor_vc_get_active_invite(get_current_user_id()) : [];
$has_active = !empty($invite['ticket']) && !empty($invite['room']) && !empty($invite['exp']) && time() <= (int)$invite['exp'];

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php bloginfo('name'); ?> — Videollamada</title>
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

.embed{
  position:relative;
  width:100%;
  aspect-ratio: 16 / 9;
  overflow:hidden;
  border-radius:14px;
  border:1px solid rgba(255,255,255,.12);
  background:rgba(0,0,0,.20);
}
.embed > *{margin:0 !important}
.embed iframe{
  position:absolute;
  inset:0;
  width:100% !important;
  height:100% !important;
  border:0 !important;
}

.small{font-size:12px;color:rgba(233,238,252,.75);line-height:1.5;margin-top:10px}
.field{width:100%;max-width:520px;padding:10px;border-radius:10px;border:1px solid rgba(255,255,255,.14);background:rgba(10,16,28,.35);color:var(--text)}
textarea.field{min-height:100px;resize:vertical}

.notice-ok{margin:10px 0;padding:10px 12px;border-radius:12px;border:1px solid rgba(120,220,160,.35);background:rgba(20,80,40,.22)}
.notice-warn{margin:10px 0;padding:10px 12px;border-radius:12px;border:1px solid rgba(255,220,120,.35);background:rgba(80,60,20,.22)}

.bottom{
  padding:14px 20px;
  border-top:1px solid var(--border);
  display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;
  color:rgba(233,238,252,.75);font-size:12px;
}
.smalllinks a{color:rgba(233,238,252,.85);text-decoration:none;border-bottom:1px dotted rgba(233,238,252,.35)}
.smalllinks a:hover{border-bottom-color:rgba(233,238,252,.85)}
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
          <a class="btn primary" href="<?php echo esc_url( home_url('/videollamada/') ); ?>">Videollamada</a>
          <a class="btn" href="<?php echo esc_url( home_url('/contacto/') ); ?>">Contacto</a>
        </nav>
      </div>

      <div class="main">
        <header class="hero">
          <h2>Videollamada</h2>
          <p>Conecta con un técnico para resolver incidencias en directo.</p>
        </header>

        <section class="grid">
          <div class="panel">
            <h3>Sala de soporte</h3>

            <?php if ($room): ?>
              <div class="embed">
                <?php
                  // FlexMeeting: usa name/width/height. [web:2229]
                  echo do_shortcode('[jitsi-meet-wp name="' . esc_attr($room) . '" width="1200" height="700"/]');
                ?>
              </div>

              <div class="small">
                Sala: <code><?php echo esc_html($room); ?></code> · Si no te carga, revisa permisos de cámara/micrófono.
              </div>

            <?php else: ?>

              <?php if ($requested): ?>
                <div class="notice-ok" id="vcStatus">
                  Solicitud enviada. Un técnico la revisará y recibirás un enlace cuando esté aprobada.
                </div>
              <?php endif; ?>

              <?php if ($has_active): ?>
                <div class="notice-warn">
                  Tienes una videollamada activa asignada. Si no te redirige, espera unos segundos.
                </div>
              <?php endif; ?>

              <p>Para entrar a una sala, primero solicita acceso. Cuando el técnico lo apruebe, entrarás automáticamente.</p>

              <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="seidor_vc_request_create">
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('seidor_vc_request_create')); ?>">

                <p style="margin:0 0 8px;color:var(--muted);font-size:13px;">ID incidencia (opcional)</p>
                <input class="field" type="number" name="ticket_id" min="1" value="<?php echo $ticket ? (int)$ticket : ''; ?>">

                <p style="margin:12px 0 8px;color:var(--muted);font-size:13px;">Motivo / detalles (opcional)</p>
                <textarea class="field" name="reason" placeholder="Ej: Incidencia crítica, necesito detallar el problema por videollamada."></textarea>

                <div style="margin-top:12px">
                  <button type="submit" class="btn primary" style="justify-content:center">Solicitar acceso a videollamada</button>
                </div>

                <div class="small">
                  Nota: cuando se apruebe, el enlace tendrá caducidad.
                </div>
              </form>

              <?php if ($requested): ?>
              <script>
              (function () {
                // Polling cada 5s mientras está esperando aprobación
                function check() {
                  var body = new URLSearchParams();
                  body.append('action', 'seidor_vc_check');

                  fetch('<?php echo esc_url( admin_url('admin-ajax.php') ); ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                    body: body.toString(),
                    credentials: 'same-origin'
                  })
                  .then(r => r.json())
                  .then(json => {
                    if (json && json.success && json.data && json.data.ready && json.data.url) {
                      window.location.href = json.data.url;
                    }
                  })
                  .catch(() => {});
                }

                check();
                setInterval(check, 5000);
              })();
              </script>
              <?php endif; ?>

            <?php endif; ?>
          </div>

          <aside class="panel">
            <h3>Acciones rápidas</h3>
            <p>Si falla, registra una incidencia.</p>

            <a class="btn" style="width:100%;justify-content:center;margin-bottom:10px"
               href="<?php echo esc_url( home_url('/incidencias/') ); ?>">
              Registrar incidencia
            </a>

            <div class="small">
              Consejo: indica navegador, hora, y si estabas en Wi‑Fi/VPN.
            </div>
          </aside>
        </section>
      </div>

      <div class="bottom">
        <div>© <?php echo date('Y'); ?> Seidor</div>
        <div class="smalllinks">
          <a href="<?php echo esc_url( home_url('/videollamada/') ); ?>">Videollamada</a> ·
          <a href="<?php echo esc_url( home_url('/politica-privacidad/') ); ?>">Privacidad</a>
        </div>
      </div>

    </div>
  </div>
</div>

<?php wp_footer(); ?>
</body>
</html>

<?php
/* Page: Solicitar diseño web (slug: solicitar-diseno-web) */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php bloginfo('name'); ?> — Solicitar diseño web</title>
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
  color-scheme: dark;
}
*{box-sizing:border-box}
.wrap{
  min-height:100vh;
  background:
    radial-gradient(1200px 600px at 20% 10%, rgba(79,124,255,.25), transparent 60%),
    radial-gradient(900px 500px at 90% 20%, rgba(106,92,255,.18), transparent 60%),
    linear-gradient(180deg,var(--bg1),var(--bg2));
  display:flex; align-items:center;
}
.container{width:100%;max-width:1100px;margin:0 auto;padding:52px 18px;color:var(--text);}
.shell{
  border:1px solid var(--border);
  border-radius:20px;
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
  color:var(--text); text-decoration:none; font-weight:800; font-size:13px;
  transition:.15s transform, .15s border-color, .15s background;
}
.btn:hover{transform:translateY(-1px);border-color:var(--border2);background:rgba(255,255,255,.08)}
.btn.primary{border-color:transparent;background:linear-gradient(135deg,var(--brand),var(--brand2))}
.main{padding:28px 20px 22px}
.card{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:16px;
  padding:16px;
}
label{display:block;margin:12px 0 6px;color:var(--muted);font-size:13px}
input, textarea, select{
  width:100%;
  border-radius:12px;
  border:1px solid var(--border);
  background:rgba(255,255,255,.05);
  color:var(--text);
  padding:10px 12px;
  outline:none;
}
select{
  background-color:#0f1a31 !important;
  border-color:rgba(255,255,255,.18) !important;
  color:#e9eefc !important;
}
select option{
  background-color:#0f1a31 !important;
  color:#e9eefc !important;
}
textarea{min-height:120px;resize:vertical}
.row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
@media (max-width:700px){.row{grid-template-columns:1fr}}

.notice-ok{
  margin:0 0 16px;
  padding:14px 16px;
  border-radius:16px;
  border:1px solid rgba(79,124,255,.55);
  background:linear-gradient(135deg, rgba(79,124,255,.18), rgba(106,92,255,.10));
  color:#e9eefc;
  box-shadow:0 12px 40px rgba(0,0,0,.25);
}
.notice-ok .t{font-weight:900;font-size:15px;margin:0 0 6px}
.notice-ok .d{color:rgba(233,238,252,.88);line-height:1.55;margin:0}

.bottom{
  padding:14px 20px;
  border-top:1px solid var(--border);
  display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;
  color:rgba(233,238,252,.75);font-size:12px;
}
.smalllinks a{color:rgba(233,238,252,.85);text-decoration:none;border-bottom:1px dotted rgba(233,238,252,.35)}
.smalllinks a:hover{border-bottom-color:rgba(233,238,252,.85)}
</style>

<?php
$enviado = ( isset($_GET['enviado']) && $_GET['enviado'] == '1' ); // [web:1404]
?>

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
          <a class="btn" href="<?php echo esc_url( home_url('/contacto/') ); ?>">Contacto</a>
        </nav>
      </div>

      <div class="main">
        <h2 style="margin:0 0 8px;font-size:34px;">Solicitar diseño web</h2>
        <p style="margin:0 0 18px; color:var(--muted); max-width:760px;">
          Rellena este formulario y registraremos tu solicitud para revisarla cuanto antes.
        </p>

        <?php if ( $enviado ) : ?>
          <div class="notice-ok">
            <p class="t">Solicitud enviada correctamente</p>
            <p class="d">
              Hemos recibido tu solicitud y ya está registrada en nuestro sistema. En breve te contactaremos por email o teléfono para confirmar los detalles y los próximos pasos.
            </p>
            <p style="margin:12px 0 0;">
              <a class="btn primary" href="<?php echo esc_url( home_url('/servicios/') ); ?>">Volver a servicios</a>
              <a class="btn" href="<?php echo esc_url( home_url('/') ); ?>">Ir al inicio</a>
            </p>
          </div>
        <?php endif; ?>

        <?php if ( ! $enviado ) : ?>
          <div class="card">
            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
              <input type="hidden" name="action" value="seidor_diseno_web">
              <?php wp_nonce_field('seidor_diseno_web', 'seidor_nonce'); ?>

              <div class="row">
                <div>
                  <label for="nombre">Nombre</label>
                  <input id="nombre" name="nombre" type="text" required>
                </div>
                <div>
                  <label for="apellidos">Apellidos</label>
                  <input id="apellidos" name="apellidos" type="text" required>
                </div>
              </div>

              <div class="row">
                <div>
                  <label for="email">Email</label>
                  <input id="email" name="email" type="email" required>
                </div>
                <div>
                  <label for="telefono">Teléfono</label>
                  <input id="telefono" name="telefono" type="tel" required placeholder="+34 600 000 000">
                </div>
              </div>

              <div class="row">
                <div>
                  <label for="web">Web/URL (si existe)</label>
                  <input id="web" name="web" type="url" placeholder="https://...">
                </div>
                <div>
                  <label for="tipo">Tipo de trabajo</label>
                  <select id="tipo" name="tipo" required>
                    <option value="landing">Landing page</option>
                    <option value="cambios">Cambios visuales/maquetación</option>
                    <option value="contenido">Contenidos / secciones</option>
                    <option value="otro">Otro</option>
                  </select>
                </div>
              </div>

              <label for="detalle">Qué necesitas</label>
              <textarea id="detalle" name="detalle" required></textarea>

              <p style="margin:14px 0 0;">
                <button class="btn primary" type="submit">Enviar solicitud</button>
                <a class="btn" href="<?php echo esc_url( home_url('/diseno-web/') ); ?>">Volver</a>
              </p>
            </form>
          </div>
        <?php endif; ?>
      </div>

      <div class="bottom">
        <div>© <?php echo date('Y'); ?> Seidor</div>
        <div class="smalllinks">
          <a href="<?php echo esc_url( home_url('/servicios/') ); ?>">Servicios</a> ·
          <a href="<?php echo esc_url( home_url('/politica-privacidad/') ); ?>">Privacidad</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php wp_footer(); ?>
</body>
</html>

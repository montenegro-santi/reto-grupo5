<?php
/* Page: Diseño web (slug: diseno-web) */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php bloginfo('name'); ?> — Diseño web</title>
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
.hero{display:flex;gap:18px;align-items:flex-start;justify-content:space-between;flex-wrap:wrap}
.hero h2{margin:0 0 8px;font-size:34px;letter-spacing:.2px}
.hero p{margin:0;max-width:760px;color:var(--muted);font-size:15px;line-height:1.6}
.card{
  display:block;
  background:var(--card);
  border:1px solid var(--border);
  border-radius:16px;
  padding:16px;
  text-decoration:none;
  color:var(--text);
}
.kicker{
  display:inline-block;margin-top:10px;font-size:12px;
  padding:4px 10px;border-radius:999px;border:1px solid var(--border);
  color:var(--muted);
}
.bullets{margin:14px 0 0; padding-left:18px; color:var(--muted); line-height:1.6}
.bottom{
  padding:14px 20px;
  border-top:1px solid var(--border);
  display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;
  color:rgba(233,238,252,.75);font-size:12px;
}
.smalllinks a{color:rgba(233,238,252,.85);text-decoration:none;border-bottom:1px dotted rgba(233,238,252,.35)}
.smalllinks a:hover{border-bottom-color:rgba(233,238,252,.85)}

/* ===== FIX: desplegable oscuro (por si añades <select> en esta página) ===== */
select{
  background-color: rgba(15,26,49,.92);
  color: var(--text);
  border: 1px solid rgba(255,255,255,.18);
}
select option{
  background-color: #0f1a31;
  color: #e9eefc;
}
select option:checked,
select option:hover{
  background-color: #1a2a55;
  color: #ffffff;
}
select, select:hover, select:focus{
  background-color: #0f1a31 !important;
  color: #e9eefc !important;
  border-color: rgba(255,255,255,.18) !important;
}

select option{
  background-color: #0f1a31 !important;
  color: #e9eefc !important;
}

/* ===== /FIX ===== */
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
          <a class="btn primary" href="<?php echo esc_url( home_url('/servicios/') ); ?>">Servicios</a>
          <a class="btn" href="<?php echo esc_url( home_url('/incidencias/') ); ?>">Incidencias</a>
          <a class="btn" href="<?php echo esc_url( home_url('/videollamada/') ); ?>">Videollamada</a>
          <a class="btn" href="<?php echo esc_url( home_url('/contacto/') ); ?>">Contacto</a>
        </nav>
      </div>

      <div class="main">
        <div class="hero">
          <div>
            <h2>Diseño web</h2>
            <p>Landing pages, cambios visuales, maquetación y contenidos.</p>

            <ul class="bullets">
              <li>Cambios de diseño y maquetación responsive.</li>
              <li>Creación de secciones/páginas nuevas.</li>
              <li>Optimización de imágenes y estilos.</li>
              <li>Ajustes de contenido y estructura.</li>
            </ul>

            <p style="margin-top:14px">
              <a class="btn primary" href="<?php echo esc_url( home_url('/solicitar-diseno-web/') ); ?>">
                Solicitar diseño web
              </a>
            </p>
          </div>
        </div>
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


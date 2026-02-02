<?php
/* page-servicios.php - Plantilla para la página /servicios/ */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php bloginfo('name'); ?> — Servicios</title>
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
  --radius:18px;
  --max:1100px;
  --font: system-ui, -apple-system, "Segoe UI", Roboto, Arial, "Noto Sans", "Helvetica Neue", sans-serif;
}
*{box-sizing:border-box}
body{margin:0;font-family:var(--font);color:var(--text)}
a{color:inherit;text-decoration:none}
a:hover{text-decoration:none}
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
.hero p{margin:0;max-width:820px;color:var(--muted);font-size:15px;line-height:1.6}
.grid{
  margin-top:16px;
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
  gap:12px;
}
.card{
  display:block;
  background:var(--card);
  border:1px solid var(--border);
  border-radius:16px;
  padding:16px;
  transition:.15s transform, .15s border-color, .15s background;
}
.card:hover{transform:translateY(-2px);border-color:var(--border2);background:rgba(15,26,49,.92)}
.card h3{margin:0 0 6px;font-size:15px}
.card p{margin:0;color:var(--muted);font-size:13px;line-height:1.45}
.kicker{
  display:inline-block;margin-top:10px;font-size:12px;
  padding:4px 10px;border-radius:999px;border:1px solid var(--border);
  color:var(--muted);
}
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
          <a class="btn primary" href="<?php echo esc_url( home_url('/servicios/') ); ?>">Servicios</a>
          <a class="btn" href="<?php echo esc_url( home_url('/incidencias/') ); ?>">Incidencias</a>
          <a class="btn" href="<?php echo esc_url( home_url('/videollamada/') ); ?>">Videollamada</a>
          <a class="btn" href="<?php echo esc_url( home_url('/contacto/') ); ?>">Contacto</a>
        </nav>
      </div>

      <div class="main">
        <header class="hero">
          <h2>Servicios</h2>
          <p>Selecciona un área para ver opciones y solicitar soporte. Si el problema es urgente, entra en Videollamada.</p>
        </header>

        <section class="grid" aria-label="Catálogo de servicios">
          <a class="card" href="<?php echo esc_url( home_url('/diseno-web/') ); ?>">
            <h3>Diseño web</h3>
            <p>Landing pages, cambios visuales, maquetación y contenidos.</p>
            <span class="kicker">Web</span>
          </a>

          <a class="card" href="<?php echo esc_url( home_url('/mantenimiento/') ); ?>">
            <h3>Mantenimiento</h3>
            <p>Actualizaciones, revisión general y tareas recurrentes.</p>
            <span class="kicker">WordPress</span>
          </a>

          <a class="card" href="<?php echo esc_url( home_url('/seguridad/') ); ?>">
            <h3>Seguridad</h3>
            <p>Hardening, SSL, revisión de vulnerabilidades y malware.</p>
            <span class="kicker">Security</span>
          </a>

          <a class="card" href="<?php echo esc_url( home_url('/copias/') ); ?>">
            <h3>Copias y restauración</h3>
            <p>Backups programados y recuperación ante fallos.</p>
            <span class="kicker">Backups</span>
          </a>

          <a class="card" href="<?php echo esc_url( home_url('/monitorizacion/') ); ?>">
            <h3>Monitorización</h3>
            <p>Uptime, alertas, logs y seguimiento de caídas.</p>
            <span class="kicker">Monitoring</span>
          </a>

          <a class="card" href="<?php echo esc_url( home_url('/rendimiento/') ); ?>">
            <h3>Rendimiento / SEO</h3>
            <p>Optimización de carga, caché, imágenes y revisión técnica.</p>
            <span class="kicker">Performance</span>
          </a>
        </section>
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

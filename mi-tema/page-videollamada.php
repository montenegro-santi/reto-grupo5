<?php
/* page-videollamada.php - /videollamada/ */
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

/* ====== FIX RESPONSIVE PARA JITSI ======
   Hacemos un “aspect ratio box” 16:9 y forzamos el iframe a ocuparlo.
   Esto evita que se vea estrecho/cortado. [web:879][web:867]
*/
.embed{
  position:relative;
  width:100%;
  aspect-ratio: 16 / 9;
  overflow:hidden;

  border-radius:14px;
  border:1px solid rgba(255,255,255,.12);
  background:rgba(0,0,0,.20);
}

/* Quita márgenes raros que puedan meter bloques/shortcodes */
.embed > *{margin:0 !important}

/* Iframe al 100% dentro del contenedor */
.embed iframe{
  position:absolute;
  inset:0;
  width:100% !important;
  height:100% !important;
  border:0 !important;
}

/* Si el plugin mete un contenedor propio con altura fija, lo “aplanamos” */
.embed :where(div, section){
  max-width:100%;
}

.small{font-size:12px;color:rgba(233,238,252,.75);line-height:1.5;margin-top:10px}

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

            <div class="embed">
              <?php
              if ( have_posts() ) :
                while ( have_posts() ) : the_post();
                  the_content(); // aquí va el shortcode [jitsi-meet-wp ...]
                endwhile;
              endif;
              ?>
            </div>

            <div class="small">
              Si no te carga: prueba en Chrome/Firefox actualizados y revisa permisos de cámara/micrófono.
            </div>
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

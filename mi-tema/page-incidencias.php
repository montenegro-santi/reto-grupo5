<?php
/* page-incidencias.php - Plantilla para la página /incidencias/ */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php bloginfo('name'); ?> — Incidencias</title>
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
  color-scheme: dark;
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
  grid-template-columns:1.2fr .8fr;
  gap:12px;
}
@media (max-width: 920px){
  .grid{grid-template-columns:1fr}
}
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
.req{color:rgba(233,238,252,.55);font-weight:700}
input, select, textarea{
  width:100%;
  padding:10px 12px;
  border-radius:12px;
  border:1px solid rgba(255,255,255,.12);
  background:rgba(255,255,255,.06);
  color:var(--text);
  outline:none;
}
textarea{min-height:120px;resize:vertical}

/* Select oscuro */
select{
  background-color: rgba(15,26,49,.92);
  color: var(--text);
  border-color: rgba(255,255,255,.18);
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

.row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
@media (max-width:700px){.row{grid-template-columns:1fr}}
.helper{font-size:12px;color:rgba(233,238,252,.70);margin-top:8px;line-height:1.4}
.kicker{
  display:inline-block;margin-top:10px;font-size:12px;
  padding:4px 10px;border-radius:999px;border:1px solid var(--border);
  color:var(--muted);
}

.notice-ok{
  margin:16px 0 0;
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
$enviado = ( isset($_GET['enviado']) && $_GET['enviado'] == '1' ); // [web:1392]
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
          <a class="btn primary" href="<?php echo esc_url( home_url('/incidencias/') ); ?>">Incidencias</a>
          <a class="btn" href="<?php echo esc_url( home_url('/videollamada/') ); ?>">Videollamada</a>
          <a class="btn" href="<?php echo esc_url( home_url('/contacto/') ); ?>">Contacto</a>
        </nav>
      </div>

      <div class="main">
        <header class="hero">
          <h2>Incidencias</h2>
          <p>Registra un ticket con la información necesaria para que el técnico lo resuelva rápido. Si es crítico, usa Videollamada.</p>

          <?php if ( $enviado ) : ?>
            <div class="notice-ok">
              <p class="t">Incidencia enviada correctamente</p>
              <p class="d">
                Hemos recibido tu incidencia y ya está registrada. Se atenderá lo más pronto posible; si se trata de una urgencia crítica, utiliza la opción de Videollamada.
              </p>
              <p style="margin:12px 0 0;">
                <a class="btn primary" href="<?php echo esc_url( home_url('/servicios/') ); ?>">Volver a servicios</a>
                <a class="btn" href="<?php echo esc_url( home_url('/') ); ?>">Ir al inicio</a>
              </p>
            </div>
          <?php endif; ?>
        </header>

        <section class="grid" aria-label="Formulario de incidencia">
          <div class="panel">
            <h3>Crear incidencia</h3>
            <p>Cuanta más información relevante aportes, más rápido podremos diagnosticar y resolver.</p>

            <?php if ( ! $enviado ) : ?>
            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
              <input type="hidden" name="action" value="seidor_incidencia">
              <?php wp_nonce_field('seidor_incidencia', 'seidor_nonce'); ?>

              <div class="row">
                <div class="field">
                  <label for="nombre">Nombre <span class="req">(obligatorio)</span></label>
                  <input id="nombre" name="nombre" type="text" required autocomplete="given-name">
                </div>
                <div class="field">
                  <label for="apellidos">Apellidos <span class="req">(obligatorio)</span></label>
                  <input id="apellidos" name="apellidos" type="text" required autocomplete="family-name">
                </div>
              </div>

              <div class="row">
                <div class="field">
                  <label for="email">Email <span class="req">(obligatorio)</span></label>
                  <input id="email" name="email" type="email" required autocomplete="email">
                </div>
                <div class="field">
                  <label for="telefono">Teléfono <span class="req">(obligatorio)</span></label>
                  <input id="telefono" name="telefono" type="tel" required placeholder="+34 600 000 000" autocomplete="tel">
                </div>
              </div>

              <div class="field">
                <label for="asunto">Asunto / Resumen <span class="req">(obligatorio)</span></label>
                <input id="asunto" name="asunto" type="text" required placeholder="Ej: Web caída / Error 500 / No carga login">
              </div>

              <div class="row">
                <div class="field">
                  <label for="servicio">Servicio afectado <span class="req">(obligatorio)</span></label>
                  <select id="servicio" name="servicio" required>
                    <option value="" selected disabled>Selecciona un servicio…</option>
                    <option value="web">Web</option>
                    <option value="email">Email</option>
                    <option value="dns">DNS / Dominio</option>
                    <option value="servidor">Servidor / Hosting</option>
                    <option value="seguridad">Seguridad</option>
                    <option value="otro">Otro</option>
                  </select>
                </div>

                <div class="field">
                  <label for="categoria">Categoría <span class="req">(obligatorio)</span></label>
                  <select id="categoria" name="categoria" required>
                    <option value="" selected disabled>Selecciona una categoría…</option>
                    <option value="acceso">Acceso / Login</option>
                    <option value="rendimiento">Rendimiento / Lentitud</option>
                    <option value="error">Error / Fallo</option>
                    <option value="caida">Caída / No disponible</option>
                    <option value="cambio">Cambio reciente / Actualización</option>
                    <option value="otro">Otro</option>
                  </select>
                </div>
              </div>

              <div class="row">
                <div class="field">
                  <label for="impacto">Impacto <span class="req">(obligatorio)</span></label>
                  <select id="impacto" name="impacto" required>
                    <option value="" selected disabled>Selecciona…</option>
                    <option value="usuario">Afecta a 1 usuario</option>
                    <option value="equipo">Afecta a un equipo/departamento</option>
                    <option value="empresa">Afecta a toda la empresa</option>
                    <option value="clientes">Afecta a clientes / servicio público</option>
                  </select>
                </div>

                <div class="field">
                  <label for="urgencia">Urgencia <span class="req">(obligatorio)</span></label>
                  <select id="urgencia" name="urgencia" required>
                    <option value="" selected disabled>Selecciona…</option>
                    <option value="baja">Baja (puede esperar)</option>
                    <option value="media">Media (hoy)</option>
                    <option value="alta">Alta (lo antes posible)</option>
                    <option value="critica">Crítica (ahora)</option>
                  </select>
                </div>
              </div>

              <div class="field">
                <label for="url">URL afectada (opcional)</label>
                <input id="url" name="url" type="url" placeholder="https://...">
              </div>

              <div class="row">
                <div class="field">
                  <label for="inicio">Cuándo empezó (aprox.) <span class="req">(obligatorio)</span></label>
                  <input id="inicio" name="inicio" type="datetime-local" required>
                </div>
                <div class="field">
                  <label for="entorno">Entorno <span class="req">(obligatorio)</span></label>
                  <select id="entorno" name="entorno" required>
                    <option value="" selected disabled>Selecciona…</option>
                    <option value="produccion">Producción</option>
                    <option value="staging">Staging / Pruebas</option>
                    <option value="local">Local</option>
                  </select>
                </div>
              </div>

              <div class="field">
                <label for="descripcion">Descripción (pasos, error, usuarios afectados) <span class="req">(obligatorio)</span></label>
                <textarea id="descripcion" name="descripcion" required placeholder="Qué ocurre, desde cuándo, pasos para reproducirlo, usuarios afectados, mensaje de error exacto..."></textarea>
              </div>

              <div class="field">
                <label for="evidencias">Evidencias (opcional)</label>
                <textarea id="evidencias" name="evidencias" placeholder="Pega logs, IDs de error, texto exacto del mensaje, etc."></textarea>
                <div class="helper">Si tienes capturas, por ahora puedes describirlas o pegarlas en un enlace; luego añadimos subida de archivos.</div>
              </div>

              <button class="btn primary" type="submit">Enviar incidencia</button>
              <div class="helper">Si es un corte total o afecta a clientes, usa “Videollamada” para acelerar la atención.</div>
            </form>
            <?php endif; ?>
          </div>

          <aside class="panel">
            <h3>Atajos</h3>
            <p>Accesos rápidos para incidencias urgentes.</p>

            <a class="btn primary" style="width:100%;justify-content:center;margin-bottom:10px"
               href="<?php echo esc_url( home_url('/videollamada/') ); ?>">
              Abrir videollamada
            </a>

            <a class="btn" style="width:100%;justify-content:center;margin-bottom:10px"
               href="<?php echo esc_url( home_url('/servicios/') ); ?>">
              Ver servicios
            </a>

            <span class="kicker">Helpdesk</span>

            <div class="helper">
              Recomendación: indica hora del fallo, qué cambió antes del problema, y el mensaje de error exacto (si lo hay).
            </div>
          </aside>
        </section>
      </div>

      <div class="bottom">
        <div>© <?php echo date('Y'); ?> Seidor</div>
        <div class="smalllinks">
          <a href="<?php echo esc_url( home_url('/incidencias/') ); ?>">Incidencias</a> ·
          <a href="<?php echo esc_url( home_url('/politica-privacidad/') ); ?>">Privacidad</a>
        </div>
      </div>

    </div>
  </div>
</div>

<?php wp_footer(); ?>
</body>
</html>

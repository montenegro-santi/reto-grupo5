<?php
if (!defined('ABSPATH')) exit;

add_action('admin_enqueue_scripts', 'seidor_vc_notification_load');
function seidor_vc_notification_load($hook) {
    $user = wp_get_current_user();
    if (strpos($user->user_login ?? '', 'seidor') === false) return;
    
    wp_enqueue_script('jquery');
    wp_add_inline_script('jquery', seidor_vc_get_js());
    wp_add_inline_style('wp-admin', seidor_vc_get_css());
}

// JS PURA (sin <script>)
function seidor_vc_get_js() {
    return "
jQuery(function($){
    function checkCalls(){
        $.post(ajaxurl, {action:'seidor_vc_check_pending'}, function(r){
            if(r.success && r.data.pendientes>0){
                showPopup(r.data.pendientes, r.data.cliente);
            }
        }).fail(function(){});
    }
    setInterval(checkCalls,3000);
    checkCalls();
    
    function showPopup(count,cliente){
        if($('#vc-modal').length) return;
        $('body').append(`
            <div id='vc-modal' class='vc-modal-bg'>
                <div class='vc-modal-box'>
                    <div class='vc-header'>
                        <h3>🚨 VIDEOLLAMADA SOLICITADA</h3>
                        <span class='vc-close'>×</span>
                    </div>
                    <div class='vc-body'>
                        <p><strong>Cliente:</strong> `+(cliente||'Nuevo')+`</p>
                        <p><strong>Pendientes:</strong> `+count+`</p>
                        <div class='vc-actions'>
                            <a href='`+ajaxurl.replace('admin-ajax.php','admin.php?page=seidor-vc-requests')+`' class='vc-btn vc-accept'>✅ APROBAR</a>
                            <button class='vc-btn vc-ignore'>❌ Ignorar</button>
                        </div>
                    </div>
                </div>
            </div>
        `);
        playSound();
        $('#vc-modal').addClass('vc-show');
        
        $('.vc-close, .vc-ignore').click(()=>$('#vc-modal').fadeOut(300,function(){$(this).remove();}));
        $('.vc-accept').click(()=>$('#vc-modal').fadeOut(300,function(){$(this).remove();}));
    }
    
    function playSound(){
        var a=new Audio();
        a.src='" . esc_js(plugins_url('beep.mp3', __FILE__)) . "';
        a.play().catch(()=>0);
    }
});
    ";
}

// CSS PURA (sin <style>)
function seidor_vc_get_css() {
    return "
.vc-modal-bg{
    position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,.85);
    z-index:999999;display:flex;align-items:center;justify-content:center;opacity:0;visibility:hidden;transition:.3s;
}
.vc-modal-bg.vc-show{opacity:1;visibility:visible;}
.vc-modal-box{
    background:linear-gradient(135deg,#ff4757,#ff3742);color:#fff;border-radius:20px;width:90%;max-width:450px;
    box-shadow:0 25px 50px rgba(255,50,50,.6);transform:scale(.8);transition:.3s;
}
.vc-modal-bg.vc-show .vc-modal-box{transform:scale(1);}
.vc-header{padding:25px 20px 15px;border-radius:20px 20px 0 0;text-align:center;}
.vc-header h3{margin:0;font-size:22px;text-shadow:0 2px 5px rgba(0,0,0,.5);}
.vc-body{padding:20px;text-align:center;}
.vc-actions{margin-top:20px;}
.vc-btn{padding:12px 24px;margin:0 8px;border:none;border-radius:30px;font-weight:700;cursor:pointer;font-size:14px;}
.vc-accept{background:#00d2a3;color:#fff;text-decoration:none;display:inline-block;}
.vc-ignore{background:rgba(255,255,255,.25);color:#fff;}
.vc-close{position:absolute;right:20px;top:18px;font-size:28px;cursor:pointer;}
    ";
}

// AJAX check pendientes
add_action('wp_ajax_seidor_vc_check_pending', 'seidor_vc_check_pending_ajax');
function seidor_vc_check_pending_ajax() {
    $user = wp_get_current_user();
    if (strpos($user->user_login ?? '', 'seidor') === false) wp_die('Unauthorized', 403);
    
    global $wpdb;
    $table = seidor_vc_requests_table();
    $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM `$table` WHERE status='pending'");
    
    $last = $wpdb->get_row("SELECT user_id FROM `$table` WHERE status='pending' ORDER BY created_at DESC LIMIT 1");
    $cliente = $last ? get_user_by('id', $last->user_id)->display_name ?? 'Cliente' : '';
    
    wp_send_json_success(['pendientes' => $count, 'cliente' => $cliente]);
}

<?php
namespace TutoriasBooking\Frontend;

function enqueue_assets()
{
    $css_rel = 'assets/css/frontend.css';
    $js_rel  = 'assets/js/frontend.js';

    wp_enqueue_style(
        'tb-frontend',
        plugins_url($css_rel, TB_PLUGIN_FILE),
        [],
        filemtime(TB_PLUGIN_DIR . $css_rel)
    );

    $deps = ['jquery'];
    if (TB_RECAPTCHA_SITE_KEY) {
        wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js', [], null, true);
        $deps[] = 'google-recaptcha';
    }

    wp_enqueue_script(
        'tb-frontend',
        plugins_url($js_rel, TB_PLUGIN_FILE),
        $deps,
        filemtime(TB_PLUGIN_DIR . $js_rel),
        true
    );
    wp_localize_script('tb-frontend', 'tbBooking', [
        'ajaxUrl' => admin_url('admin-ajax.php')
    ]);
}
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets');

function render_form_shortcode($atts = [])
{
    global $wpdb;

    $atts = shortcode_atts([
        'width' => ''
    ], $atts, 'formulario_dni');

    $container_style = '';
    if (!empty($atts['width'])) {
        $container_style = 'max-width:' . esc_attr($atts['width']) . ';';
    }

    // Variables iniciales utilizadas en la plantilla.
    $dni_verified        = '';
    $email_verified      = '';
    $exam_date_selected  = '';
    $current_date        = date('Y-m-d');
    $tutores             = $wpdb->get_results("SELECT id, nombre FROM {$wpdb->prefix}tutores ORDER BY nombre ASC");

    ob_start();
    include TB_PLUGIN_DIR . 'templates/frontend/booking-form.php';
    return ob_get_clean();
}
add_shortcode('formulario_dni', __NAMESPACE__ . '\\render_form_shortcode');

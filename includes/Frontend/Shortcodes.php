<?php
namespace TutoriasBooking\Frontend;

function enqueue_assets()
{
    $css_rel = 'assets/css/frontend.css';
    $js_rel  = 'assets/js/frontend.js';

    // Ensure our stylesheet loads after Elementor's if present
    $style_deps = [];
    if (wp_style_is('elementor-frontend', 'enqueued')) {
        $style_deps[] = 'elementor-frontend';
    }
    if (wp_style_is('elementor-pro', 'enqueued')) {
        $style_deps[] = 'elementor-pro';
    }

    wp_enqueue_style(
        'tb-frontend',
        plugins_url($css_rel, TB_PLUGIN_FILE),
        $style_deps,
        filemtime(TB_PLUGIN_DIR . $css_rel)
    );

    $deps = ['jquery'];
    if (TB_RECAPTCHA_SITE_KEY) {
        $recaptcha_src = 'https://www.google.com/recaptcha/api.js';
        $lang = getenv('TB_RECAPTCHA_LANGUAGE');
        if ($lang) {
            $recaptcha_src = \add_query_arg('hl', $lang, $recaptcha_src);
        }
        wp_enqueue_script('google-recaptcha', $recaptcha_src, [], false, true);
        wp_script_add_data('google-recaptcha', 'async', true);
        wp_script_add_data('google-recaptcha', 'defer', true);
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
// Load assets after most styles/scripts so our CSS takes precedence
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets', PHP_INT_MAX);

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

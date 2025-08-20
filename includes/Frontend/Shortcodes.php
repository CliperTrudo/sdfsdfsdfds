<?php
namespace TutoriasBooking\Frontend;

function enqueue_assets()
{
    wp_enqueue_style('tb-frontend', plugins_url('../../assets/css/frontend.css', __FILE__));
    wp_enqueue_script('tb-frontend', plugins_url('../../assets/js/frontend.js', __FILE__), ['jquery'], false, true);
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
        $container_style = 'style="max-width:' . esc_attr($atts['width']) . ';"';
    }

    $current_date = date('Y-m-d');
    $dni_verified = '';
    $email_verified = '';
    $exam_date_selected = '';
    $tutores = $wpdb->get_results("SELECT id, nombre FROM {$wpdb->prefix}tutores ORDER BY nombre ASC");

    ob_start();
    include TB_PLUGIN_DIR . 'templates/frontend/dni-form.php';
    include TB_PLUGIN_DIR . 'templates/frontend/exam-date-form.php';
    include TB_PLUGIN_DIR . 'templates/frontend/tutor-selection-calendar.php';

    return ob_get_clean();
}
add_shortcode('formulario_dni', __NAMESPACE__ . '\\render_form_shortcode');
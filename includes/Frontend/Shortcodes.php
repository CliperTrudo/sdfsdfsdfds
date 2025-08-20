<?php
namespace TutoriasBooking\Frontend;

function enqueue_assets()
{
    $deps = [];
    if (wp_style_is('elementor-frontend', 'registered')) {
        $deps[] = 'elementor-frontend';
    }

    wp_enqueue_style('tb-frontend', TB_PLUGIN_URL . 'assets/css/frontend.css', $deps);
    wp_enqueue_script('tb-frontend', TB_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], false, true);

    wp_localize_script('tb-frontend', 'tbBooking', [
        'ajaxUrl' => admin_url('admin-ajax.php')
    ]);
}
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets', 20);

function render_form_shortcode($atts = [])
{
    global $wpdb;

    $atts = shortcode_atts([
        'width' => ''
    ], $atts, 'formulario_dni');

    $container_style = '';
    $hidden_style = 'style="display:none;"';
    if (!empty($atts['width'])) {
        $max_width = esc_attr($atts['width']);
        $container_style = 'style="max-width:' . $max_width . ';"';
        $hidden_style = 'style="max-width:' . $max_width . ';display:none;"';
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
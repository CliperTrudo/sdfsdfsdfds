<?php
namespace TutoriasBooking\Frontend;

function enqueue_styles()
{
    wp_enqueue_style(
        'tbk-frontend',
        plugins_url('../../assets/css/frontend.css', __FILE__),
        ['elementor-frontend'],
        '1.0'
    );
}
add_action('elementor/frontend/after_enqueue_styles', __NAMESPACE__ . '\\enqueue_styles');

function enqueue_scripts()
{
    wp_enqueue_script(
        'tbk-frontend',
        plugins_url('../../assets/js/frontend.js', __FILE__),
        ['jquery'],
        '1.0',
        true
    );
    wp_localize_script('tbk-frontend', 'tbBooking', [
        'ajaxUrl' => admin_url('admin-ajax.php')
    ]);
}
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts', 20);

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
    echo '<div id="tbk">';
    include TB_PLUGIN_DIR . 'templates/frontend/dni-form.php';
    include TB_PLUGIN_DIR . 'templates/frontend/exam-date-form.php';
    include TB_PLUGIN_DIR . 'templates/frontend/tutor-selection-calendar.php';
    echo '</div>';

    return ob_get_clean();
}
add_shortcode('formulario_dni', __NAMESPACE__ . '\\render_form_shortcode');

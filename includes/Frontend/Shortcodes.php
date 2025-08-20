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

    ob_start();

    $current_date = date('Y-m-d');

    // Contenedor principal que agrupa los tres pasos del formulario
    echo '<div class="tb-container" ' . $container_style . '>';

    // Paso 1: Verificación de DNI
    include TB_PLUGIN_DIR . 'templates/frontend/dni-form.php';

    // Paso 2: Selección de fecha de examen (inicialmente oculto)
    include TB_PLUGIN_DIR . 'templates/frontend/exam-date-form.php';

    // Paso 3: Selección de tutor y franja horaria (inicialmente oculto)
    $tutores = $wpdb->get_results("SELECT id, nombre FROM {$wpdb->prefix}tutores ORDER BY nombre ASC");
    include TB_PLUGIN_DIR . 'templates/frontend/tutor-selection-calendar.php';

    echo '</div>';

    return ob_get_clean();
}
add_shortcode('formulario_dni', __NAMESPACE__ . '\\render_form_shortcode');

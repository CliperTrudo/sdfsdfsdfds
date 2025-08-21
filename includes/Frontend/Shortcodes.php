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

    $alumnos_reserva_table = $wpdb->prefix . 'alumnos_reserva';
    $show_dni_form = true;
    $show_exam_date_form = false;
    $show_tutor_selection = false;
    $dni_verified = '';
    $email_verified = '';
    $exam_date_selected = '';
    $current_date = date('Y-m-d');

    if (isset($_POST['tb_submit_exam_date']) && !empty($_POST['tb_exam_date'])) {
        $dni_verified = sanitize_text_field($_POST['tb_dni_verified']);
        $email_verified = sanitize_email($_POST['tb_email_verified']);
        $exam_date_selected = sanitize_text_field($_POST['tb_exam_date']);
        if ($exam_date_selected < $current_date) {
            echo '<div class="tb-message tb-message-error">La fecha del examen no puede ser anterior a hoy.</div>';
            $show_dni_form = false;
            $show_exam_date_form = true;
        } else {
            $show_dni_form = false;
            $show_exam_date_form = false;
            $show_tutor_selection = true;
        }
    } elseif (isset($_POST['tb_submit_dni']) && !empty($_POST['tb_dni']) && !empty($_POST['tb_email'])) {
        $dni_input   = sanitize_text_field($_POST['tb_dni']);
        $email_input = sanitize_email($_POST['tb_email']);
        $alumno = $wpdb->get_row($wpdb->prepare("SELECT tiene_cita FROM {$alumnos_reserva_table} WHERE dni = %s AND email = %s", $dni_input, $email_input));
        if ($alumno) {
            if (intval($alumno->tiene_cita) === 0) {
                $dni_verified = $dni_input;
                $email_verified = $email_input;
                $show_dni_form = false;
                $show_exam_date_form = true;
            } else {
                echo '<div class="tb-message tb-message-error">El DNI introducido ya tiene una cita registrada. Si necesitas otra cita, por favor, contacta con la administración.</div>';
            }
        } else {
            echo '<div class="tb-message tb-message-error">El DNI y el correo electrónico proporcionados no se encuentran en nuestra base de datos de alumnos de reserva. Por favor, contacta con la administración.</div>';
        }
    }

    if ($show_dni_form) {
        include TB_PLUGIN_DIR . 'templates/frontend/dni-form.php';
    }
    if ($show_exam_date_form) {
        include TB_PLUGIN_DIR . 'templates/frontend/exam-date-form.php';
    }
    if ($show_tutor_selection) {
        $tutores = $wpdb->get_results("SELECT id, nombre FROM {$wpdb->prefix}tutores ORDER BY nombre ASC");
        include TB_PLUGIN_DIR . 'templates/frontend/tutor-selection-calendar.php';
    }

    return ob_get_clean();
}
add_shortcode('formulario_dni', __NAMESPACE__ . '\\render_form_shortcode');

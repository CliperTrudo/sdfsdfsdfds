<?php
namespace TutoriasBooking\Admin;

use TutoriasBooking\Core\AppointmentService;

class AppointmentsList {
    public static function render() {
        if (!current_user_can('manage_options')) {
            wp_die('Permisos insuficientes');
        }
        $filters = [
            'from'   => isset($_GET['from']) ? sanitize_text_field($_GET['from']) : '',
            'to'     => isset($_GET['to']) ? sanitize_text_field($_GET['to']) : '',
            'estado' => isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '',
        ];
        $client = isset($_GET['cliente']) ? sanitize_text_field($_GET['cliente']) : '';
        $appointments = AppointmentService::list($filters);
        if ($client) {
            $appointments = array_filter($appointments, function($cita) use ($client) {
                return stripos($cita->participant_name, $client) !== false || stripos($cita->participant_email, $client) !== false;
            });
        }
        $page     = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $total    = count($appointments);
        $pages    = (int) ceil($total / $per_page);
        $appointments = array_slice($appointments, ($page - 1) * $per_page, $per_page);
        require TB_PLUGIN_DIR . 'templates/admin/appointments-list.php';
    }
}

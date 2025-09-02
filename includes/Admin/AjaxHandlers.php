<?php
namespace TutoriasBooking\Admin;

use TutoriasBooking\Google\CalendarService;
use TutoriasBooking\Admin\AppointmentsController;

class AjaxHandlers {
    public static function init() {
        add_action('wp_ajax_tb_get_day_availability', [self::class, 'ajax_get_day_availability']);
        add_action('wp_ajax_tb_edit_appointment', [self::class, 'ajax_edit_appointment']);
        add_action('wp_ajax_tb_upload_attachment', [self::class, 'ajax_upload_attachment']);
        add_action('wp_ajax_tb_delete_attachment', [self::class, 'ajax_delete_attachment']);
    }

    public static function ajax_get_day_availability() {
        check_ajax_referer('tb_get_day_availability', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes.');
        }
        $tutor_id = isset($_POST['tutor_id']) ? intval($_POST['tutor_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        if (!$tutor_id || empty($date)) {
            wp_send_json_error('Datos incompletos.');
        }
        $events = CalendarService::get_available_calendar_events($tutor_id, $date, $date);
        $slots = [];
        $madridTz = new \DateTimeZone('Europe/Madrid');
        foreach ($events as $ev) {
            if (isset($ev->start->dateTime) && isset($ev->end->dateTime)) {
                $start = new \DateTime($ev->start->dateTime);
                $start->setTimezone($madridTz);
                $end = new \DateTime($ev->end->dateTime);
                $end->setTimezone($madridTz);
                $slots[] = $start->format('H:i') . '-' . $end->format('H:i');
            }
        }
        wp_send_json_success($slots);
    }

    public static function ajax_edit_appointment() {
        check_ajax_referer('tb_edit_appointment', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes.');
        }

        $result = AppointmentsController::handle_edit();
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        wp_send_json_success($result);
    }

    public static function ajax_upload_attachment() {
        check_ajax_referer('tb_upload_attachment', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes.');
        }

        if (empty($_FILES['attachment']) || empty($_POST['cita_id'])) {
            wp_send_json_error('Datos incompletos.');
        }

        $file = $_FILES['attachment'];
        $allowed = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($file['type'], $allowed, true)) {
            wp_send_json_error('Tipo de archivo no permitido.');
        }

        $uploaded = wp_handle_upload($file, ['test_form' => false]);
        if (isset($uploaded['error'])) {
            wp_send_json_error($uploaded['error']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'tb_cita_adjuntos';
        $wpdb->insert(
            $table,
            [
                'cita_id'    => intval($_POST['cita_id']),
                'file_url'   => esc_url_raw($uploaded['url']),
                'uploaded_by'=> get_current_user_id(),
                'uploaded_at'=> current_time('mysql'),
            ],
            ['%d', '%s', '%d', '%s']
        );

        wp_send_json_success(['url' => $uploaded['url']]);
    }

    public static function ajax_delete_attachment() {
        check_ajax_referer('tb_delete_attachment', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes.');
        }

        $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
        if (!$attachment_id) {
            wp_send_json_error('ID inválido.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'tb_cita_adjuntos';
        $attachment = $wpdb->get_row($wpdb->prepare("SELECT file_url FROM {$table} WHERE id = %d", $attachment_id));
        if ($attachment) {
            $uploads = wp_upload_dir();
            $file_path = str_replace($uploads['baseurl'], $uploads['basedir'], $attachment->file_url);
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
            $wpdb->delete($table, ['id' => $attachment_id], ['%d']);
        }

        wp_send_json_success();
    }
}

<?php
namespace TutoriasBooking\Admin;

use TutoriasBooking\Google\CalendarService;

class AjaxHandlers {
    public static function init() {
        add_action('wp_ajax_tb_get_day_availability', [self::class, 'ajax_get_day_availability']);
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
}

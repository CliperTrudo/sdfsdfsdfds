<?php
namespace TutoriasBooking\Admin;

use TutoriasBooking\Google\CalendarService;

class AjaxHandlers {
    public static function init() {
        add_action('wp_ajax_tb_get_day_availability', [self::class, 'ajax_get_day_availability']);
        add_action('wp_ajax_tb_list_events', [self::class, 'ajax_list_events']);
        add_action('wp_ajax_tb_update_event', [self::class, 'ajax_update_event']);
        add_action('wp_ajax_tb_delete_event', [self::class, 'ajax_delete_event']);
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

    public static function ajax_list_events() {
        check_ajax_referer('tb_events_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes.');
        }
        global $wpdb;
        $tutor_id  = isset($_POST['tutor_id']) ? intval($_POST['tutor_id']) : 0;
        $startRaw  = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $endRaw    = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        $dni       = isset($_POST['dni']) ? sanitize_text_field($_POST['dni']) : '';
        $modalidad = isset($_POST['modalidad']) ? sanitize_text_field($_POST['modalidad']) : '';

        $madridTz = new \DateTimeZone('Europe/Madrid');

        try {
            $startObj = !empty($startRaw) ? new \DateTime($startRaw, $madridTz) : null;
            $endObj   = !empty($endRaw)   ? new \DateTime($endRaw,   $madridTz) : null;

            if (!$startObj && !$endObj) {
                $today   = new \DateTime('now', $madridTz);
                $startObj = clone $today;
                $endObj   = clone $today;
            } elseif (!$startObj) {
                $startObj = clone $endObj;
            } elseif (!$endObj) {
                $endObj = clone $startObj;
            }
        } catch (\Exception $e) {
            wp_send_json_error('Rango de fechas inválido.');
        }

        if (!$startObj || !$endObj) {
            wp_send_json_error('No se pudo determinar un rango de fechas.');
        }

        $start = $startObj->format('Y-m-d');
        $end   = $endObj->format('Y-m-d');

        $tutor_ids = $tutor_id > 0
            ? [$tutor_id]
            : $wpdb->get_col("SELECT id FROM {$wpdb->prefix}tutores");

        $data = [];

        foreach ($tutor_ids as $tid) {
            $events = CalendarService::get_busy_calendar_events($tid, $start, $end, $dni);

            $tutor_name = $wpdb->get_var($wpdb->prepare("SELECT nombre FROM {$wpdb->prefix}tutores WHERE id=%d", $tid));

            foreach ($events as $ev) {
                if (isset($ev->summary) && strtoupper(trim($ev->summary)) === 'DISPONIBLE') {
                    continue; // omitir slots de disponibilidad
                }
                if (!empty($modalidad)) {
                    $desc_modalidad = '';
                    if (!empty($ev->description) && preg_match('/Modalidad:\s*(.+)/i', $ev->description, $m)) {
                        $desc_modalidad = strtolower(trim($m[1]));
                    }
                    if ($desc_modalidad !== strtolower($modalidad)) {
                        continue;
                    }
                }
                if (isset($ev->start->dateTime) && isset($ev->end->dateTime)) {
                    $startObj = new \DateTime($ev->start->dateTime);
                    $startObj->setTimezone($madridTz);
                    $endObj   = new \DateTime($ev->end->dateTime);
                    $endObj->setTimezone($madridTz);

                    $user_name = '';
                    if (!empty($ev->description) && preg_match('/Nombre:\s*(.*)\n/', $ev->description, $m)) {
                        $user_name = trim($m[1]);
                    }

                    $data[] = [
                        'id'       => $ev->id,
                        'user'     => $user_name,
                        'tutor'    => $tutor_name,
                        'start'    => $startObj->format('Y-m-d H:i'),
                        'end'      => $endObj->format('Y-m-d H:i'),
                        'url'      => $ev->hangoutLink ?? '',
                        'tutor_id' => $tid,
                    ];
                }
            }
        }

        wp_send_json_success($data);
    }

    public static function ajax_update_event() {
        check_ajax_referer('tb_events_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes.');
        }
        $tutor_id = isset($_POST['tutor_id']) ? intval($_POST['tutor_id']) : 0;
        $original_tutor_id = isset($_POST['original_tutor_id']) ? intval($_POST['original_tutor_id']) : $tutor_id;
        $event_id = isset($_POST['event_id']) ? sanitize_text_field($_POST['event_id']) : '';
        $summary  = isset($_POST['summary']) ? sanitize_text_field($_POST['summary']) : null;
        $start    = isset($_POST['start']) ? sanitize_text_field($_POST['start']) : '';
        $end      = isset($_POST['end']) ? sanitize_text_field($_POST['end']) : '';
        if (!$tutor_id || empty($event_id) || empty($start) || empty($end)) {
            wp_send_json_error('Datos incompletos.');
        }
        $madrid = new \DateTimeZone('Europe/Madrid');
        $utc    = new \DateTimeZone('UTC');
        try {
            $startObj = new \DateTime($start, $madrid);
            $endObj   = new \DateTime($end, $madrid);
            $startUtc = $startObj->setTimezone($utc)->format('c');
            $endUtc   = $endObj->setTimezone($utc)->format('c');
        } catch (\Exception $e) {
            wp_send_json_error('Formato de fecha inválido.');
        }

        if ($tutor_id !== $original_tutor_id) {
            global $wpdb;
            $old = $wpdb->get_row($wpdb->prepare("SELECT calendar_id FROM {$wpdb->prefix}tutores WHERE id=%d", $original_tutor_id));
            if (!$old || empty($old->calendar_id)) {
                wp_send_json_error('Tutor original inválido.');
            }
            $old_service = CalendarService::get_calendar_service($original_tutor_id);
            if (!$old_service) {
                wp_send_json_error('No se pudo acceder al calendario original.');
            }
            try {
                $event = $old_service->events->get($old->calendar_id, $event_id);
            } catch (\Exception $e) {
                wp_send_json_error('No se pudo obtener la cita original.');
            }
            $summary      = $event->getSummary();
            $description  = $event->getDescription();
            $attendees    = [];
            if ($event->getAttendees()) {
                foreach ($event->getAttendees() as $a) {
                    if ($a->getEmail()) {
                        $attendees[] = $a->getEmail();
                    }
                }
            }
            $new_event = CalendarService::create_calendar_event($tutor_id, $summary, $description, $startUtc, $endUtc, $attendees);
            if (is_wp_error($new_event)) {
                wp_send_json_error($new_event->get_error_message());
            }
            $del = CalendarService::delete_calendar_event($original_tutor_id, $event_id);
            if (is_wp_error($del)) {
                wp_send_json_error($del->get_error_message());
            }
            wp_send_json_success([
                'event_id' => $new_event->id,
                'url'      => $new_event->hangoutLink ?? '',
            ]);
        } else {
            $res = CalendarService::update_calendar_event($tutor_id, $event_id, $summary, null, $startUtc, $endUtc);
            if (is_wp_error($res)) {
                wp_send_json_error($res->get_error_message());
            }
            wp_send_json_success([
                'event_id' => $event_id,
                'url'      => $res->hangoutLink ?? '',
            ]);
        }
    }

    public static function ajax_delete_event() {
        check_ajax_referer('tb_events_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes.');
        }
        global $wpdb;
        $tutor_id = isset($_POST['tutor_id']) ? intval($_POST['tutor_id']) : 0;
        $event_id = isset($_POST['event_id']) ? sanitize_text_field($_POST['event_id']) : '';
        if (!$tutor_id || empty($event_id)) {
            wp_send_json_error('Datos incompletos.');
        }

        $event = CalendarService::get_event($tutor_id, $event_id);
        if (is_wp_error($event)) {
            wp_send_json_error($event->get_error_message());
        }

        $description = $event->getDescription();
        $summary     = $event->getSummary();
        $dni = null;
        $modalidad = null;

        if (!empty($description)) {
            if (preg_match('/DNI:\s*([A-Z0-9]+)/i', $description, $m)) {
                $dni = trim($m[1]);
            }
            if (preg_match('/Modalidad:\s*(Online|Presencial)/i', $description, $m)) {
                $modalidad = strtolower($m[1]);
            }
        }
        if (!$dni && !empty($summary) && preg_match('/-\s*([A-Z0-9]+)$/i', $summary, $m)) {
            $dni = trim($m[1]);
        }
        if (!$modalidad && !empty($summary) && preg_match('/\b(ONLINE|PRESENCIAL)\b/i', $summary, $m)) {
            $modalidad = strtolower($m[1]);
        }

        if (!$dni || !$modalidad) {
            wp_send_json_error('No se pudo extraer el DNI o la modalidad del evento.');
        }

        $res = CalendarService::delete_calendar_event($tutor_id, $event_id);
        if (is_wp_error($res)) {
            wp_send_json_error($res->get_error_message());
        }

        $field = $modalidad === 'online' ? 'online' : 'presencial';
        $wpdb->update("{$wpdb->prefix}alumnos_reserva", [$field => 1], ['dni' => $dni]);

        wp_send_json_success();
    }
}
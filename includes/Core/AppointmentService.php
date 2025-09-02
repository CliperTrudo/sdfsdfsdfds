<?php
namespace TutoriasBooking\Core;

use TutoriasBooking\Google\CalendarService;

/**
 * Servicio para gestionar citas.
 * Incluye listados con filtros, edición y reprogramación.
 */
class AppointmentService {
    /**
     * Obtener lista de citas con filtros opcionales.
     *
     * @param array $args ['tutor_id'=>int,'from'=>string,'to'=>string,'estado'=>string]
     * @return array
     */
    public static function list($args = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'tb_citas';
        $where  = '1=1';
        $params = [];
        if (!empty($args['tutor_id'])) {
            $where .= ' AND tutor_id = %d';
            $params[] = intval($args['tutor_id']);
        }
        if (!empty($args['from'])) {
            $where .= ' AND start_datetime >= %s';
            $params[] = $args['from'];
        }
        if (!empty($args['to'])) {
            $where .= ' AND start_datetime <= %s';
            $params[] = $args['to'];
        }
        if (!empty($args['estado'])) {
            $where .= ' AND estado = %s';
            $params[] = $args['estado'];
        }
        $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE {$where} ORDER BY start_datetime ASC", $params);
        return $wpdb->get_results($sql);
    }

    /**
     * Actualizar campos no relacionados con el horario.
     *
     * @param int   $id   ID de la cita
     * @param array $data ['participant_name'=>string,'description'=>string]
     * @return bool
     */
    public static function update_details($id, $data) {
        if (!current_user_can('edit_posts')) {
            return false;
        }
        global $wpdb;
        $table  = $wpdb->prefix . 'tb_citas';
        $fields = [];
        $format = [];
        if (isset($data['participant_name'])) {
            $fields['participant_name'] = sanitize_text_field($data['participant_name']);
            $format[] = '%s';
        }
        if (isset($data['description'])) {
            $fields['description'] = sanitize_textarea_field($data['description']);
            $format[] = '%s';
        }
        if (empty($fields)) {
            return false;
        }
        $fields['updated_at'] = current_time('mysql');
        $format[] = '%s';
        $updated = $wpdb->update($table, $fields, ['id' => intval($id)], $format, ['%d']);
        if ($updated !== false) {
            self::log_history($id, 'editar', $fields);
        }
        return $updated !== false;
    }

    /**
     * Reprogramar una cita.
     *
     * @param int    $id        ID de la cita
     * @param string $new_start Fecha y hora inicio (UTC) Y-m-d H:i:s
     * @param string $new_end   Fecha y hora fin (UTC)
     * @param array  $args      Campos extra ['location'=>string]
     * @return true|\WP_Error
     */
    public static function reschedule($id, $new_start, $new_end, $args = []) {
        if (!current_user_can('edit_posts')) {
            return new \WP_Error('forbidden', 'Permisos insuficientes');
        }
        global $wpdb;
        $table = $wpdb->prefix . 'tb_citas';
        $cita  = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", intval($id)));
        if (!$cita) {
            return new \WP_Error('not_found', 'Cita no encontrada');
        }
        $date = gmdate('Y-m-d', strtotime($new_start));
        $busy = CalendarService::get_busy_calendar_events($cita->tutor_id, $date, $date);
        $new_start_ts = strtotime($new_start);
        $new_end_ts   = strtotime($new_end);
        foreach ($busy as $ev) {
            if ($ev->id === $cita->event_id) {
                continue;
            }
            $ev_start = strtotime($ev->start->dateTime);
            $ev_end   = strtotime($ev->end->dateTime);
            if ($ev_start < $new_end_ts && $ev_end > $new_start_ts) {
                return new \WP_Error('overlap', 'La nueva franja se solapa con otra cita.');
            }
        }
        $changes = [
            'start' => $new_start,
            'end'   => $new_end,
        ];
        if (isset($args['location'])) {
            $changes['location'] = sanitize_text_field($args['location']);
        }
        $event = CalendarService::update_calendar_event($cita->tutor_id, $cita->event_id, $changes);
        if (is_wp_error($event)) {
            return $event;
        }
        $fields = [
            'start_datetime' => $new_start,
            'end_datetime'   => $new_end,
            'updated_at'     => current_time('mysql'),
        ];
        if (isset($changes['location'])) {
            $fields['location'] = $changes['location'];
        }
        $wpdb->update($table, $fields, ['id' => $id], array_fill(0, count($fields), '%s'), ['%d']);
        self::log_history($id, 'reprogramar', $fields);
        // Notificar a las partes
        $subject = 'Cita reprogramada';
        $message = 'Tu cita ha sido reprogramada para el ' . $new_start;
        wp_mail($cita->participant_email, $subject, $message);
        $tutor = $wpdb->get_row($wpdb->prepare("SELECT email FROM {$wpdb->prefix}tutores WHERE id = %d", $cita->tutor_id));
        if ($tutor) {
            wp_mail($tutor->email, $subject, $message);
        }
        return true;
    }

    /**
     * Guardar entrada de historial.
     */
    private static function log_history($cita_id, $accion, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'tb_citas_historial';
        $wpdb->insert(
            $table,
            [
                'cita_id' => intval($cita_id),
                'usuario' => get_current_user_id(),
                'accion'  => $accion,
                'datos'   => maybe_serialize($data),
                'fecha'   => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );
    }
}

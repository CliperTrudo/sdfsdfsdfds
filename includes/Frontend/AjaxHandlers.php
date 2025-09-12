<?php
namespace TutoriasBooking\Frontend;

use TutoriasBooking\Google\CalendarService;

/**
 * Handles AJAX requests from the booking form.
 *
 * Prior to the project restructure this file contained plain functions
 * hooked directly to WordPress actions.  The {@see Loader} now expects a
 * class with an `init()` method so we wrap the previous behaviour inside a
 * class while keeping the original logic intact.
 */
class AjaxHandlers {
    private static function debug_log($message) {
        if (defined('TB_DEBUG') && TB_DEBUG) {
            error_log($message);
        }
    }

    /**
     * Register AJAX hooks.
     */
    public static function init() {
        // Hook para obtener franjas horarias disponibles (para usuarios logueados y no logueados)
        add_action('wp_ajax_tb_get_available_slots', [self::class, 'ajax_get_available_slots']);
        add_action('wp_ajax_nopriv_tb_get_available_slots', [self::class, 'ajax_get_available_slots']);
        // Hook para procesar la reserva (para usuarios logueados y no logueados)
        add_action('wp_ajax_tb_process_booking', [self::class, 'ajax_process_booking']);
        add_action('wp_ajax_nopriv_tb_process_booking', [self::class, 'ajax_process_booking']);
        // Verificación de DNI y correo
        add_action('wp_ajax_tb_verify_dni', [self::class, 'ajax_verify_dni']);
        add_action('wp_ajax_nopriv_tb_verify_dni', [self::class, 'ajax_verify_dni']);
        self::debug_log('TutoriasBooking: AjaxHandlers::init() - Hooks AJAX registrados.');
    }

    /**
     * Fetch available slots for a tutor using Google Calendar data.
     * This function is called via AJAX from the frontend.
     */
    public static function ajax_get_available_slots() {
        self::debug_log('TutoriasBooking: ajax_get_available_slots() - Solicitud recibida.');

        // Verificar el nonce de seguridad para prevenir ataques CSRF
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_booking_nonce')) {
            self::debug_log('TutoriasBooking: ajax_get_available_slots() - ERROR: Nonce inválido.');
            wp_send_json_error('Error de seguridad. Nonce inválido.');
            return;
        }
        self::debug_log('TutoriasBooking: ajax_get_available_slots() - Nonce verificado correctamente.');

        // Recoger y sanear los datos de la solicitud POST
        $tutor_id       = isset($_POST['tutor_id']) ? intval($_POST['tutor_id']) : 0;
        $start_date_str = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date_str   = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        $exam_date_str  = isset($_POST['exam_date']) ? sanitize_text_field($_POST['exam_date']) : '';
        $modalidad      = isset($_POST['modalidad']) ? sanitize_text_field($_POST['modalidad']) : '';

        self::debug_log("TutoriasBooking: ajax_get_available_slots() - Datos recibidos: tutor_id={$tutor_id}, start_date={$start_date_str}, end_date={$end_date_str}, exam_date=" . ($exam_date_str ?: 'N/A'));

        // Validar que los datos esenciales estén presentes
        if (!$tutor_id || empty($start_date_str) || empty($end_date_str)) {
            self::debug_log('TutoriasBooking: ajax_get_available_slots() - ERROR: Datos incompletos para la consulta de disponibilidad.');
            wp_send_json_error('Datos incompletos para la consulta de disponibilidad.');
            return;
        }

        // Intentar crear objetos DateTime para las fechas, con la zona horaria de Madrid
        // Aseguramos que todas las fechas se manejen en la misma zona horaria para comparaciones consistentes.
        try {
            $madrid_timezone = new \DateTimeZone('Europe/Madrid');
            $start_date_obj = new \DateTime($start_date_str, $madrid_timezone);
            // Para el end_date_obj, ajustamos la hora al final del día para incluir todo el día en el rango.
            // Si el end_date_str solo contiene la fecha, se asume 00:00:00, lo que excluiría el último día.
            $end_date_obj   = new \DateTime($end_date_str . ' 23:59:59', $madrid_timezone);
            if (!empty($exam_date_str)) {
                $exam_date_obj  = new \DateTime($exam_date_str, $madrid_timezone);
                self::debug_log("TutoriasBooking: ajax_get_available_slots() - Fechas parseadas (Europe/Madrid): start_date_obj={$start_date_obj->format('Y-m-d H:i:s')}, end_date_obj={$end_date_obj->format('Y-m-d H:i:s')}, exam_date_obj={$exam_date_obj->format('Y-m-d H:i:s')}");
            } else {
                self::debug_log("TutoriasBooking: ajax_get_available_slots() - Fechas parseadas (Europe/Madrid): start_date_obj={$start_date_obj->format('Y-m-d H:i:s')}, end_date_obj={$end_date_obj->format('Y-m-d H:i:s')}, sin exam_date");
            }
        } catch (\Exception $e) {
            self::debug_log('TutoriasBooking: ajax_get_available_slots() - ERROR: Formato de fecha inválido - ' . $e->getMessage());
            wp_send_json_error('Formato de fecha inválido en la consulta.');
            return;
        }
        if (!empty($exam_date_str)) {
            // Restringir el rango de búsqueda a [exam_date - 7 días, exam_date - 1 día]
            $range_start = (clone $exam_date_obj)->sub(new \DateInterval('P7D'))->setTime(0, 0, 0);
            $range_end   = (clone $exam_date_obj)->sub(new \DateInterval('P1D'))->setTime(23, 59, 59);

            if ($start_date_obj < $range_start) {
                $start_date_obj = clone $range_start;
            }
            if ($start_date_obj > $range_end) {
                $start_date_obj = clone $range_end;
            }
            if ($end_date_obj > $range_end) {
                $end_date_obj = clone $range_end;
            }
            if ($end_date_obj < $range_start) {
                $end_date_obj = clone $range_start;
            }

            if ($start_date_obj > $end_date_obj) {
                self::debug_log('TutoriasBooking: ajax_get_available_slots() - ERROR: Rango de fechas fuera de los límites permitidos por la fecha de examen.');
                wp_send_json_error('Rango de fechas inválido para la consulta de disponibilidad.');
                return;
            }

            self::debug_log("TutoriasBooking: ajax_get_available_slots() - Rango ajustado por fecha de examen: {$start_date_obj->format('Y-m-d H:i:s')} a {$end_date_obj->format('Y-m-d H:i:s')}");
        } else {
            if ($start_date_obj > $end_date_obj) {
                self::debug_log('TutoriasBooking: ajax_get_available_slots() - ERROR: start_date posterior a end_date.');
                wp_send_json_error('Rango de fechas inválido para la consulta de disponibilidad.');
                return;
            }

            self::debug_log("TutoriasBooking: ajax_get_available_slots() - Rango utilizado sin fecha de examen: {$start_date_obj->format('Y-m-d H:i:s')} a {$end_date_obj->format('Y-m-d H:i:s')}");
        }

        // Formatear las fechas para la consulta al servicio de calendario
        $start_date_for_query = $start_date_obj->format('Y-m-d');
        $end_date_for_query   = $end_date_obj->format('Y-m-d');
        self::debug_log("TutoriasBooking: ajax_get_available_slots() - Fechas para consulta: start={$start_date_for_query}, end={$end_date_for_query}");

        // Obtener eventos marcados como "DISPONIBLE" del calendario del tutor
        $available_events = CalendarService::get_available_calendar_events($tutor_id, $start_date_for_query, $end_date_for_query, $modalidad);
        self::debug_log('TutoriasBooking: ajax_get_available_slots() - Eventos DISPONIBLE obtenidos: ' . count($available_events) . ' eventos.');
        // self::debug_log('TutoriasBooking: available_events: ' . print_r($available_events, true)); // Descomentar para ver el detalle de los eventos

        $available_slots = [];
        // Generar franjas de 45 minutos a partir de los eventos "DISPONIBLE"
        foreach ($available_events as $event) {
            // Convert Google Calendar event times from UTC to Europe/Madrid
            $event_start = new \DateTime($event->start->dateTime);
            $event_start->setTimezone($madrid_timezone);
            $event_end   = new \DateTime($event->end->dateTime);
            $event_end->setTimezone($madrid_timezone);
            self::debug_log("TutoriasBooking: Processing available event from {$event_start->format('Y-m-d H:i')} to {$event_end->format('Y-m-d H:i')}");

            $current_slot_start = clone $event_start;
            $interval = new \DateInterval('PT45M'); // Intervalo de 45 minutos

            // Iterar para crear slots de 45 minutos hasta que se exceda el fin del evento
            while ($current_slot_start->add($interval) <= $event_end) {
                $slot_end = clone $current_slot_start; // El fin del slot es el current_slot_start después de añadir 45min
                $current_slot_start->sub($interval); // Retroceder current_slot_start para obtener el inicio del slot
                $available_slots[] = [
                    'date'       => $current_slot_start->format('Y-m-d'),
                    'start_time' => $current_slot_start->format('H:i'),
                    'end_time'   => $slot_end->format('H:i')
                ];
                self::debug_log("TutoriasBooking: Generated slot: {$current_slot_start->format('Y-m-d H:i')} - {$slot_end->format('H:i')}");
                $current_slot_start->add($interval); // Avanzar current_slot_start para el siguiente ciclo
            }
        }
        self::debug_log('TutoriasBooking: ajax_get_available_slots() - Total slots generados antes de filtrar: ' . count($available_slots));
        // self::debug_log('TutoriasBooking: generated_available_slots: ' . print_r($available_slots, true)); // Descomentar para ver el detalle

        // Filtrar franjas que ya estén ocupadas en el calendario
        // OBTENER TODOS LOS EVENTOS QUE NO SON "DISPONIBLE". ESTOS SON LOS EVENTOS OCUPADOS/NO DISPONIBLES.
        $busy_events = CalendarService::get_busy_calendar_events($tutor_id, $start_date_for_query, $end_date_for_query, '', $modalidad);
        self::debug_log('TutoriasBooking: ajax_get_available_slots() - Eventos OCUPADOS obtenidos: ' . count($busy_events) . ' eventos.');
        // self::debug_log('TutoriasBooking: busy_events: ' . print_r($busy_events, true)); // Descomentar para ver el detalle

        $busy_intervals = [];
        // Convertir los eventos ocupados en intervalos de DateTime para facilitar la comparación
        foreach ($busy_events as $event) {
            // Convert Google Calendar busy event times from UTC to Europe/Madrid
            $start = new \DateTime($event->start->dateTime);
            $start->setTimezone($madrid_timezone);
            $end   = new \DateTime($event->end->dateTime);
            $end->setTimezone($madrid_timezone);
            $busy_intervals[] = ['start' => $start, 'end' => $end];
            self::debug_log("TutoriasBooking: Busy interval: from {$start->format('Y-m-d H:i')} to {$end->format('Y-m-d H:i')}");
        }
        self::debug_log('TutoriasBooking: ajax_get_available_slots() - Total busy intervals: ' . count($busy_intervals));
        // self::debug_log('TutoriasBooking: busy_intervals: ' . print_r($busy_intervals, true)); // Descomentar para ver el detalle

        $filtered_slots = [];
        // COMPARAR CADA SLOT DE 45 MINUTOS GENERADO CON LOS INTERVALOS OCUPADOS.
        // SOLO LOS SLOTS QUE NO SE SOLAPEN CON NINGÚN EVENTO OCUPADO SERÁN INCLUIDOS.
        foreach ($available_slots as $slot) {
            $slot_start = new \DateTime($slot['date'] . ' ' . $slot['start_time'], $madrid_timezone);
            $slot_end   = new \DateTime($slot['date'] . ' ' . $slot['end_time'], $madrid_timezone);
            $overlap = false;
            // Comprobar si el slot se solapa con algún intervalo ocupado
            foreach ($busy_intervals as $interval) {
                $is_interval_start_before_slot_end = $interval['start'] < $slot_end;
                $is_interval_end_after_slot_start = $interval['end'] > $slot_start;

                self::debug_log("TutoriasBooking: Comparing slot [{$slot_start->format('Y-m-d H:i')}-{$slot_end->format('Y-m-d H:i')}] with busy interval [{$interval['start']->format('Y-m-d H:i')}-{$interval['end']->format('Y-m-d H:i')}].");
                self::debug_log("TutoriasBooking: Condition 1: ({$interval['start']->format('Y-m-d H:i')} < {$slot_end->format('Y-m-d H:i')}) is " . ($is_interval_start_before_slot_end ? 'TRUE' : 'FALSE'));
                self::debug_log("TutoriasBooking: Condition 2: ({$interval['end']->format('Y-m-d H:i')} > {$slot_start->format('Y-m-d H:i')}) is " . ($is_interval_end_after_slot_start ? 'TRUE' : 'FALSE'));

                // Condición de solapamiento: (inicio_intervalo < fin_slot) AND (fin_intervalo > inicio_slot)
                // Si esta condición es verdadera, significa que el slot se superpone con un evento ocupado.
                if ($is_interval_start_before_slot_end && $is_interval_end_after_slot_start) {
                    $overlap = true;
                    self::debug_log("TutoriasBooking: OVERLAP CONFIRMED: Slot {$slot['date']} {$slot['start_time']}-{$slot['end_time']} overlaps with busy interval {$interval['start']->format('Y-m-d H:i')}-{$interval['end']->format('Y-m-d H:i')}.");
                    break; // Si hay solapamiento, no necesitamos comprobar más intervalos para este slot.
                } else {
                    self::debug_log("TutoriasBooking: NO OVERLAP: Slot {$slot['date']} {$slot['start_time']}-{$slot['end_time']} does NOT overlap with busy interval {$interval['start']->format('Y-m-d H:i')}-{$interval['end']->format('Y-m-d H:i')}.");
                }
            }
            // Si no hay solapamiento, añadir el slot a las franjas filtradas que se enviarán al frontend.
            // ESTO ASEGURA QUE SOLO LAS FRANJAS REALMENTE LIBRES SE MUESTREN.
            if (!$overlap) {
                $filtered_slots[] = $slot;
                self::debug_log("TutoriasBooking: Slot ADDED to filtered_slots: {$slot['date']} {$slot['start_time']}-{$slot['end_time']}. Final overlap status: " . ($overlap ? 'TRUE' : 'FALSE'));
            } else {
                self::debug_log("TutoriasBooking: Slot DISCARDED due to overlap: {$slot['date']} {$slot['start_time']}-{$slot['end_time']}. Final overlap status: " . ($overlap ? 'TRUE' : 'FALSE'));
            }
        }
        // Aplicar límite de 16 horas desde la hora actual de Madrid
        $limit_datetime = new \DateTime('now', $madrid_timezone);
        $limit_datetime->add(new \DateInterval('PT16H'));
        $final_slots = [];
        foreach ($filtered_slots as $slot) {
            $slot_start_dt = new \DateTime($slot['date'] . ' ' . $slot['start_time'], $madrid_timezone);
            if ($slot_start_dt >= $limit_datetime) {
                $final_slots[] = $slot;
            } else {
                self::debug_log("TutoriasBooking: Slot discarded (<16h): {$slot['date']} {$slot['start_time']}-{$slot['end_time']}");
            }
        }
        $filtered_slots = $final_slots;

        self::debug_log('TutoriasBooking: ajax_get_available_slots() - Total slots filtrados (disponibles): ' . count($filtered_slots));
        // self::debug_log('TutoriasBooking: filtered_slots: ' . print_r($filtered_slots, true)); // Descomentar para ver el detalle final

        // Enviar las franjas horarias filtradas (disponibles) al frontend en formato JSON
        wp_send_json_success($filtered_slots);
        self::debug_log('TutoriasBooking: ajax_get_available_slots() - Respuesta JSON enviada.');
    }

    /**
     * Verifica que el DNI y el correo existan en la base de datos y no tengan cita previa.
     */
    public static function ajax_verify_dni() {
        global $wpdb;

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_booking_nonce')) {
            wp_send_json_error('Error de seguridad. Nonce inválido.');
        }

        // Verify Google reCAPTCHA v2 if keys are defined
        if (TB_RECAPTCHA_SECRET_KEY) {
            $recaptcha_response = sanitize_text_field($_POST['g-recaptcha-response'] ?? '');
            if (empty($recaptcha_response)) {
                wp_send_json_error('Error en la validación de reCAPTCHA.');
            }
            $verify = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret'   => TB_RECAPTCHA_SECRET_KEY,
                    'response' => $recaptcha_response,
                    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
                ],
            ]);
            if (is_wp_error($verify)) {
                wp_send_json_error('No se pudo verificar reCAPTCHA.');
            }
            $verify_body = json_decode(wp_remote_retrieve_body($verify), true);
            if (empty($verify_body['success'])) {
                wp_send_json_error('La verificación de reCAPTCHA ha fallado.');
            }
        }

        $dni   = sanitize_text_field($_POST['dni'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');

        if (empty($dni) || empty($email)) {
            wp_send_json_error('Faltan datos.');
        }

        $alumnos_reserva_table = $wpdb->prefix . 'alumnos_reserva';
        $alumno = $wpdb->get_row($wpdb->prepare("SELECT online, presencial FROM {$alumnos_reserva_table} WHERE dni = %s AND email = %s", $dni, $email));

        if ($alumno) {
            // Verificar incoherencias con eventos existentes en Google Calendar
            $updated_fields = [];
            if ($alumno->online && CalendarService::has_event_by_dni_and_modality($dni, 'online')) {
                $alumno->online = 0;
                $updated_fields['online'] = 0;
            }
            if ($alumno->presencial && CalendarService::has_event_by_dni_and_modality($dni, 'presencial')) {
                $alumno->presencial = 0;
                $updated_fields['presencial'] = 0;
            }
            if (!empty($updated_fields)) {
                $wpdb->update($alumnos_reserva_table, $updated_fields, ['dni' => $dni, 'email' => $email]);
            }

            if (!$alumno->online && !$alumno->presencial) {
                wp_send_json_error('Los datos introducidos ya tienen una cita registrada. Si necesitas otra cita, por favor, contacta con la administración.');
            }

            wp_send_json_success([
                'online'     => (bool) $alumno->online,
                'presencial' => (bool) $alumno->presencial
            ]);
        } else {
            wp_send_json_error('Los datos proporcionados no se encuentran en nuestra base de datos. Por favor, contacta con la administración.');
        }
    }

    /**
     * Process a booking request and create the calendar event.
     * This function is called via AJAX when the student confirms a booking.
     */
    public static function ajax_process_booking() {
        global $wpdb; // Acceso a la clase global de la base de datos de WordPress
        self::debug_log('TutoriasBooking: ajax_process_booking() - Solicitud recibida.');

        // Verificar el nonce de seguridad
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_booking_nonce')) {
            self::debug_log('TutoriasBooking: ajax_process_booking() - ERROR: Nonce inválido.');
            wp_send_json_error('Error de seguridad. Nonce inválido.');
            return;
        }
        self::debug_log('TutoriasBooking: ajax_process_booking() - Nonce verificado correctamente.');

        // Recoger y sanear los datos de la reserva
        $dni       = sanitize_text_field($_POST['dni']);
        $email     = sanitize_email($_POST['email']);
        $modalidad = sanitize_text_field($_POST['modalidad'] ?? '');
        $alumno_data = $wpdb->get_row($wpdb->prepare("SELECT email, nombre, apellido, online, presencial FROM {$wpdb->prefix}alumnos_reserva WHERE dni = %s", $dni));
        $nombreAlumno   = $alumno_data ? $alumno_data->nombre : '';
        $apellidoAlumno = $alumno_data ? $alumno_data->apellido : '';
        $email_db       = $alumno_data ? $alumno_data->email : '';
        if (!$alumno_data || strcasecmp($email_db, $email) !== 0) {
            self::debug_log('TutoriasBooking: ajax_process_booking() - ERROR: El correo proporcionado no coincide con el registrado.');
            wp_send_json_error('El correo electrónico no coincide con el registrado.');
            return;
        }
        if (!in_array($modalidad, ['online', 'presencial'], true)) {
            wp_send_json_error('Modalidad inválida.');
            return;
        }
        if (($modalidad === 'online' && !$alumno_data->online) || ($modalidad === 'presencial' && !$alumno_data->presencial)) {
            wp_send_json_error('Ya has consumido tu permiso para la modalidad ' . $modalidad . '.');
            return;
        }
        $tutor_id   = intval($_POST['tutor_id']);
        $exam_date  = sanitize_text_field($_POST['exam_date']);
        $start_time = sanitize_text_field($_POST['start_time']);
        $end_time   = sanitize_text_field($_POST['end_time']);

        self::debug_log("TutoriasBooking: ajax_process_booking() - Datos recibidos: DNI={$dni}, Email Alumno={$email}, Tutor ID={$tutor_id}, Fecha Examen={$exam_date}, Hora Inicio={$start_time}, Hora Fin={$end_time}");

        // Validar que todos los datos esenciales para la reserva estén presentes
        if (empty($dni) || empty($email) || !$tutor_id || empty($exam_date) || empty($start_time) || empty($end_time)) {
            self::debug_log('TutoriasBooking: ajax_process_booking() - ERROR: Faltan datos esenciales para la reserva.');
            wp_send_json_error('Faltan datos esenciales para la reserva.');
            return;
        }

        // Formatear las fechas y horas a formato ISO 8601 y convertirlas a UTC
        $start_datetime_iso = $exam_date . 'T' . $start_time . ':00';
        $end_datetime_iso   = $exam_date . 'T' . $end_time   . ':00';
        $madrid_timezone = new \DateTimeZone('Europe/Madrid');
        $utc_timezone    = new \DateTimeZone('UTC');
        $start_dt_obj = new \DateTime($start_datetime_iso, $madrid_timezone);
        $end_dt_obj   = new \DateTime($end_datetime_iso,   $madrid_timezone);

        // Validar que la franja seleccionada esté al menos 16 horas en el futuro
        $limit_datetime = new \DateTime('now', $madrid_timezone);
        $limit_datetime->add(new \DateInterval('PT16H'));
        if ($start_dt_obj < $limit_datetime) {
            self::debug_log('TutoriasBooking: ajax_process_booking() - ERROR: Slot less than 16h ahead.');
            wp_send_json_error('Las reservas deben realizarse con al menos 16 horas de antelación.');
            return;
        }

        $start_datetime_utc = $start_dt_obj->setTimezone($utc_timezone)->format('c');
        $end_datetime_utc   = $end_dt_obj->setTimezone($utc_timezone)->format('c');
        self::debug_log("TutoriasBooking: ajax_process_booking() - Datetime ISO UTC: Start={$start_datetime_utc}, End={$end_datetime_utc}");

        // Obtener los datos del tutor de la base de datos
        $tutor = $wpdb->get_row($wpdb->prepare("SELECT nombre, email FROM {$wpdb->prefix}tutores WHERE id = %d", $tutor_id));
        if (!$tutor) {
            self::debug_log('TutoriasBooking: ajax_process_booking() - ERROR: Tutor no encontrado con ID: ' . $tutor_id);
            wp_send_json_error('Tutor no encontrado.');
            return;
        }
        self::debug_log("TutoriasBooking: ajax_process_booking() - Tutor encontrado: Nombre={$tutor->nombre}, Email={$tutor->email}");

        // Preparar los detalles del evento para Google Calendar utilizando un identificador
        $dni_hash = hash('sha256', $dni);
        $summary   = 'Tutoría de Examen - ' . $dni_hash;

        if ($modalidad === 'online') {
            $description = <<<EOT
Estimado alumno,
Tu simulacro de entrevista personal ha sido agendado. Revisa la invitación del calendario para conocer fecha, hora y enlace.
La sesión tendrá una duración aproximada de 45 minutos.
Si no asistes, no se reagendará.
Gracias por confiar en Academia Prefortia.

Modalidad: Online
ID: {$dni_hash}
EOT;
        } else {
            $description = <<<EOT
Estimado alumno,
Tu simulacro de entrevista personal ha sido agendado. Revisa la invitación del calendario para conocer la dirección y hora asignadas.
La sesión tendrá una duración aproximada de 45 minutos.
Si no asistes, no se reagendará.
Gracias por confiar en Academia Prefortia.

Modalidad: Presencial
ID: {$dni_hash}
EOT;
        }

        $attendees = [$email, $tutor->email]; // Asistentes del evento
        self::debug_log("TutoriasBooking: ajax_process_booking() - Detalles del evento: Summary='{$summary}', Attendees=" . implode(', ', $attendees));

        // Verificar que la franja seleccionada siga disponible antes de crear el evento
        self::debug_log('TutoriasBooking: ajax_process_booking() - Checking for busy events.');
        $busy_events = CalendarService::get_busy_calendar_events($tutor_id, $exam_date, $exam_date, '', $modalidad);
        self::debug_log('TutoriasBooking: ajax_process_booking() - Busy events found: ' . count($busy_events));
        foreach ($busy_events as $busy_event) {
            $busy_start = new \DateTime($busy_event->start->dateTime);
            $busy_start->setTimezone($madrid_timezone);
            $busy_end = new \DateTime($busy_event->end->dateTime);
            $busy_end->setTimezone($madrid_timezone);
            if ($busy_start < $end_dt_obj && $busy_end > $start_dt_obj) {
                self::debug_log('TutoriasBooking: ajax_process_booking() - ERROR: Selected slot overlaps with busy event.');
                wp_send_json_error('La franja horaria seleccionada ya no está disponible. Por favor, elige otra.');
                return;
            }
        }

        // Crear el evento en Google Calendar a través del CalendarService utilizando UTC
        $event = CalendarService::create_calendar_event($tutor_id, $summary, $description, $start_datetime_utc, $end_datetime_utc, $attendees, $modalidad);

        if (is_wp_error($event)) {
            self::debug_log('TutoriasBooking: ajax_process_booking() - Error al crear evento de Google Calendar: ' . $event->get_error_message());
            wp_send_json_error('Error al crear el evento de Google Calendar: ' . $event->get_error_message());
            return;
        }


        // Si el evento se creó con éxito en Google Calendar
        if ($event) {
            error_log('TutoriasBooking: ajax_process_booking() - Evento de Google Calendar creado con éxito. Event ID: ' . $event->id . ', Meet Link: ' . $event->hangoutLink);

            // El evento "DISPONIBLE" original se mantiene en el calendario
            error_log('TutoriasBooking: ajax_process_booking() - Nota: el evento DISPONIBLE no se elimina y permanece en el calendario.');

            // Marcar en la base de datos que el alumno ya tiene una cita en esta modalidad
            $updated = $wpdb->update(
                "{$wpdb->prefix}alumnos_reserva",
                [$modalidad => 0],
                ['dni' => $dni]
            );
            if ($updated === false) {
                error_log('TutoriasBooking: ajax_process_booking() - ERROR: No se pudo actualizar el estado de cita para el DNI ' . $dni . '. ' . $wpdb->last_error);
            }

            // Calcular el día de la semana de la fecha del examen
            $day_of_week = date_i18n('l', strtotime($exam_date));


            // Enviar correos electrónicos a alumno y tutor con los datos de la cita
            $student_subject = 'Confirmación de tutoría';
            $student_message = "Hola {$nombreAlumno},\n\nTu cita de tutoría ({$modalidad}) ha sido confirmada.\nFecha: {$exam_date} ({$day_of_week})\nHora: {$start_time} - {$end_time}\nTutor: {$tutor->nombre}\nEnlace de la reunión: {$event->hangoutLink}\n\nGracias.";
            wp_mail($email, $student_subject, $student_message);

            $tutor_subject = 'Nueva tutoría reservada';
            $tutor_message = "Se ha reservado una tutoría ({$modalidad}) con {$nombreAlumno} {$apellidoAlumno} ({$dni}).\nFecha: {$exam_date} ({$day_of_week})\nHora: {$start_time} - {$end_time}\nEmail del alumno: {$email}\nEnlace de la reunión: {$event->hangoutLink}";
            wp_mail($tutor->email, $tutor_subject, $tutor_message);

            // Enviar respuesta de éxito al frontend con los detalles de la reserva
            wp_send_json_success([
                'message'            => 'Reserva ' . $modalidad . ' confirmada. Revisa tu correo para más detalles.',
                'meet_link'          => $event->hangoutLink,
                'event_id'           => $event->id,
                'exam_date'          => $exam_date,
                'start_time'         => $start_time,
                'end_time'           => $end_time,
                'start_datetime_utc' => $start_datetime_utc,
                'end_datetime_utc'   => $end_datetime_utc,
                'day_of_week'        => $day_of_week,
                'student_first_name' => $nombreAlumno,
                'student_last_name'  => $apellidoAlumno,
                'modalidad'          => $modalidad
            ]);
            self::debug_log('TutoriasBooking: ajax_process_booking() - Respuesta JSON de éxito enviada.');
        } else {
            // Si hubo un error al crear el evento en Google Calendar
            self::debug_log('TutoriasBooking: ajax_process_booking() - ERROR: Fallo al crear el evento en Google Calendar.');
            wp_send_json_error('Error al crear el evento en Google Calendar. Por favor, inténtalo de nuevo.');
        }
    }
}
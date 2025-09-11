<?php
namespace TutoriasBooking\Admin;

use TutoriasBooking\Google\CalendarService;

/**
 * Handles logic for the admin page and prepares data for the view.
 */
class AdminController {
    /**
     * Render the admin page.
     */
    public static function handle_page() {
        global $wpdb;

        if (isset($_GET['action']) && $_GET['action'] === 'tb_assign_availability') {
            $tutor_id = isset($_GET['tutor_id']) ? intval($_GET['tutor_id']) : 0;
            self::handle_assign_availability($tutor_id);
            return;
        }

        $messages = [];

        $alumnos_reserva_table = $wpdb->prefix . 'alumnos_reserva';
        $table_exists          = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $alumnos_reserva_table)) === $alumnos_reserva_table;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('tb_admin_action', 'tb_admin_nonce');
        }

        if (isset($_POST['tb_import_tutores']) && !empty($_FILES['tb_tutores_file']['tmp_name'])) {
            $imported  = self::import_tutores_from_xlsx($_FILES['tb_tutores_file']['tmp_name']);
            $messages[] = ['type' => 'success', 'text' => 'Se importaron ' . $imported . ' tutores.'];
        }

        if ($table_exists && isset($_POST['tb_import_alumnos']) && !empty($_FILES['tb_alumnos_file']['tmp_name'])) {
            $imported  = self::import_alumnos_from_xlsx($_FILES['tb_alumnos_file']['tmp_name'], $alumnos_reserva_table);
            $messages[] = ['type' => 'success', 'text' => 'Se importaron ' . $imported . ' alumnos de reserva.'];
        }

        if (!$table_exists) {
            $messages[] = [
                'type' => 'error',
                'text' => 'La tabla de alumnos de reserva (' . esc_html($alumnos_reserva_table) . ') no se encuentra en la base de datos. Desactiva y vuelve a activar el plugin.'
            ];
        }

        if (!empty($_POST['tb_nombre']) && !empty($_POST['tb_email']) && !isset($_POST['tb_add_alumno_reserva'])) {
            $wpdb->insert(
                $wpdb->prefix . 'tutores',
                [
                    'nombre'      => sanitize_text_field($_POST['tb_nombre']),
                    'email'       => sanitize_email($_POST['tb_email']),
                    'calendar_id' => sanitize_text_field($_POST['tb_email'])
                ]
            );
            $messages[] = ['type' => 'success', 'text' => 'Tutor añadido.'];
        }

        if ($table_exists && isset($_POST['tb_add_alumno_reserva'])) {
            $dni_alumno      = sanitize_text_field($_POST['tb_alumno_dni']);
            $email_alumno    = sanitize_email($_POST['tb_alumno_email']);
            $nombre_alumno   = sanitize_text_field($_POST['tb_alumno_nombre']);
            $apellido_alumno = sanitize_text_field($_POST['tb_alumno_apellido']);
            $online_alumno   = isset($_POST['tb_alumno_online']) ? 1 : 0;
            $presencial_alumno = isset($_POST['tb_alumno_presencial']) ? 1 : 0;

            if (!empty($dni_alumno) && !empty($email_alumno) && !empty($nombre_alumno) && !empty($apellido_alumno)) {
                $existing_alumno = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$alumnos_reserva_table} WHERE dni = %s",
                    $dni_alumno
                ));

                
                if ($existing_alumno > 0) {
                    $messages[] = ['type' => 'error', 'text' => 'El DNI ' . esc_html($dni_alumno) . ' ya existe en la tabla de alumnos de reserva.'];
                } else {
                    $inserted = $wpdb->insert(
                        $alumnos_reserva_table,
                        [
                            'dni'        => $dni_alumno,
                            'email'      => $email_alumno,
                            'nombre'     => $nombre_alumno,
                            'apellido'   => $apellido_alumno,
                            'online'     => $online_alumno,
                            'presencial' => $presencial_alumno
                        ]
                    );
                    if ($inserted) {
                        $messages[] = ['type' => 'success', 'text' => 'Alumno de reserva añadido correctamente.'];
                    } else {
                        $messages[] = ['type' => 'error', 'text' => 'Error al añadir el alumno de reserva.'];
                    }
                }
            } else {
                $messages[] = ['type' => 'error', 'text' => 'Todos los campos son obligatorios para añadir un alumno de reserva.'];
            }
        }

        if (isset($_POST['tb_delete_tutor_id'])) {
            $tutor_id = intval($_POST['tb_delete_tutor_id']);
            $deleted  = $wpdb->delete(
                $wpdb->prefix . 'tutores',
                ['id' => $tutor_id],
                ['%d']
            );
            $wpdb->delete(
                $wpdb->prefix . 'tutores_tokens',
                ['tutor_id' => $tutor_id],
                ['%d']
            );
            if ($deleted !== false) {
                $messages[] = ['type' => 'success', 'text' => 'Tutor eliminado.'];
            } else {
                $messages[] = ['type' => 'error', 'text' => 'Error al eliminar el tutor.'];
            }
        }

        if (isset($_POST['tb_delete_all_tutores'])) {
            $wpdb->query("DELETE FROM {$wpdb->prefix}tutores");
            $wpdb->query("DELETE FROM {$wpdb->prefix}tutores_tokens");
            $messages[] = ['type' => 'success', 'text' => 'Todos los tutores han sido eliminados.'];
        }

        if ($table_exists && isset($_POST['tb_delete_alumno_id'])) {
            $alumno_id = intval($_POST['tb_delete_alumno_id']);
            $deleted   = $wpdb->delete(
                $alumnos_reserva_table,
                ['id' => $alumno_id],
                ['%d']
            );
            if ($deleted !== false) {
                $messages[] = ['type' => 'success', 'text' => 'Alumno eliminado de la reserva.'];
            } else {
                $messages[] = ['type' => 'error', 'text' => 'Error al eliminar el alumno de la reserva.'];
            }
        }

        if ($table_exists && isset($_POST['tb_delete_all_alumnos'])) {
            $wpdb->query("DELETE FROM {$alumnos_reserva_table}");
            $messages[] = ['type' => 'success', 'text' => 'Todos los alumnos de reserva han sido eliminados.'];
        }

        if ($table_exists && isset($_POST['tb_update_alumno_id'])) {
            $alumno_id  = intval($_POST['tb_update_alumno_id']);
            $online     = isset($_POST['tb_online']) ? 1 : 0;
            $presencial = isset($_POST['tb_presencial']) ? 1 : 0;

            $updated = $wpdb->update(
                $alumnos_reserva_table,
                [
                    'online'     => $online,
                    'presencial' => $presencial,
                ],
                ['id' => $alumno_id],
                ['%d', '%d'],
                ['%d']
            );

            if ($updated !== false) {
                $messages[] = ['type' => 'success', 'text' => 'Valores actualizados para el alumno con ID ' . esc_html($alumno_id) . '.'];
            } else {
                $messages[] = ['type' => 'error', 'text' => 'Error al actualizar los valores del alumno.'];
            }
        }

        $tutores = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tutores");
        $alumnos_reserva = [];
        $current_page    = 1;
        $total_pages     = 1;
        $search_dni      = isset($_GET['tb_search_dni']) ? sanitize_text_field($_GET['tb_search_dni']) : '';
        $per_page        = 20;

        if ($table_exists) {
            if ($search_dni !== '') {
                $alumnos_reserva = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT id, dni, nombre, apellido, email, online, presencial FROM {$alumnos_reserva_table} WHERE dni = %s",
                        $search_dni
                    )
                );
            } else {
                $current_page = isset($_GET['tb_page']) ? max(1, intval($_GET['tb_page'])) : 1;
                $offset       = ($current_page - 1) * $per_page;
                $alumnos_reserva = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT id, dni, nombre, apellido, email, online, presencial FROM {$alumnos_reserva_table} ORDER BY id LIMIT %d OFFSET %d",
                        $per_page,
                        $offset
                    )
                );
                $total_alumnos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$alumnos_reserva_table}");
                $total_pages   = (int) ceil($total_alumnos / $per_page);
            }
        }

        include TB_PLUGIN_DIR . 'templates/admin/admin-page.php';
    }

    private static function handle_assign_availability($tutor_id) {
        global $wpdb;

        $messages = [];
        $tutor = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutores WHERE id=%d", $tutor_id));
        if (!$tutor) {
            $messages[] = ['type' => 'error', 'text' => 'Tutor no encontrado.'];
            $existing_dates = [];
            self::render_assign_availability($tutor, $messages, $existing_dates);
            return;
        }

        $edit_date = isset($_GET['edit_date']) ? sanitize_text_field($_GET['edit_date']) : '';

        $start_range = date('Y-m-d');
        $end_range   = date('Y-m-d', strtotime('+' . TB_MAX_MONTHS . ' months'));
        $events      = CalendarService::get_available_calendar_events($tutor_id, $start_range, $end_range);
        $busy_events = CalendarService::get_busy_calendar_events($tutor_id, $start_range, $end_range);
        $madridTz    = new \DateTimeZone('Europe/Madrid');
        $existing_dates = [];
        foreach (array_merge($events, $busy_events) as $ev) {
            if (isset($ev->start->dateTime) && isset($ev->end->dateTime)) {
                $start = new \DateTime($ev->start->dateTime);
                $start->setTimezone($madridTz);
                $end = new \DateTime($ev->end->dateTime);
                $end->setTimezone($madridTz);
            } elseif (isset($ev->start->date) && isset($ev->end->date)) {
                $start = new \DateTime($ev->start->date, $madridTz);
                $end   = new \DateTime($ev->end->date, $madridTz);
                $end->modify('-1 day');
            } else {
                continue;
            }

            $current = clone $start;
            while ($current <= $end) {
                $existing_dates[] = $current->format('Y-m-d');
                $current->modify('+1 day');
            }
        }
        $existing_dates = array_values(array_unique($existing_dates));

        $edit_ranges = [];
        if ($edit_date) {
            $day_events = CalendarService::get_available_calendar_events($tutor_id, $edit_date, $edit_date);
            foreach ($day_events as $ev) {
                if (isset($ev->start->dateTime) && isset($ev->end->dateTime)) {
                    $start = new \DateTime($ev->start->dateTime);
                    $start->setTimezone($madridTz);
                    $end = new \DateTime($ev->end->dateTime);
                    $end->setTimezone($madridTz);
                    $summary = $ev->summary ?? '';
                    $mod = 'online';
                    if (stripos($summary, 'PRESENCIAL') !== false) {
                        $mod = 'presencial';
                    } elseif (stripos($summary, 'ONLINE') !== false) {
                        $mod = 'online';
                    }
                    $edit_ranges[] = [
                        'start'     => $start->format('H:i'),
                        'end'       => $end->format('H:i'),
                        'modality'  => $mod,
                    ];
                }
            }
            $existing_dates = array_values(array_diff($existing_dates, [$edit_date]));
        }

        $availability_hash = md5(json_encode($events));

        if (isset($_POST['tb_assign_availability'])) {
            check_admin_referer('tb_assign_availability_action', 'tb_assign_availability_nonce');

            $submitted_hash = isset($_POST['tb_availability_hash']) ? sanitize_text_field($_POST['tb_availability_hash']) : '';
            if ($submitted_hash !== $availability_hash) {
                $messages[] = ['type' => 'error', 'text' => 'La disponibilidad ha cambiado. Recarga la página e inténtalo de nuevo.'];
            } else {

            $starts = isset($_POST['tb_start_time']) ? array_map('sanitize_text_field', (array) $_POST['tb_start_time']) : [];
            $ends   = isset($_POST['tb_end_time']) ? array_map('sanitize_text_field', (array) $_POST['tb_end_time']) : [];
            $modalities = isset($_POST['tb_modality']) ? array_map('sanitize_text_field', (array) $_POST['tb_modality']) : [];
            $dates  = isset($_POST['tb_dates']) ? array_map('sanitize_text_field', (array) $_POST['tb_dates']) : [];
            $editing_date = isset($_POST['tb_editing_date']) ? sanitize_text_field($_POST['tb_editing_date']) : '';
            $original_events = [];
            if (!empty($editing_date)) {
                $dates = [$editing_date];
                $original_events = CalendarService::get_available_calendar_events($tutor_id, $editing_date, $editing_date);
            }
            $dates = array_unique($dates);
            if ($editing_date && empty($starts) && empty($ends)) {
                $busy_events = CalendarService::get_busy_calendar_events($tutor_id, $editing_date, $editing_date);
                if (!empty($busy_events)) {
                    $madridTz = new \DateTimeZone('Europe/Madrid');
                    foreach ($busy_events as $ev) {
                        if (isset($ev->start->dateTime) && isset($ev->end->dateTime)) {
                            $start = new \DateTime($ev->start->dateTime);
                            $start->setTimezone($madridTz);
                            $end = new \DateTime($ev->end->dateTime);
                            $end->setTimezone($madridTz);
                            $messages[] = [
                                'type' => 'error',
                                'text' => sprintf(
                                    'No se puede eliminar la disponibilidad porque existe la cita "%s" el %s de %s a %s.',
                                    $ev->summary ?? '',
                                    $start->format('Y-m-d'),
                                    $start->format('H:i'),
                                    $end->format('H:i')
                                )
                            ];
                        }
                    }
                } else {
                    CalendarService::delete_available_events_for_date($tutor_id, $editing_date);
                    $messages[] = ['type' => 'success', 'text' => 'Disponibilidad eliminada correctamente.'];
                    $redirect = admin_url('admin.php?page=tb-tutores&action=tb_assign_availability&tutor_id=' . $tutor_id);
                    if ( wp_safe_redirect( $redirect ) ) {
                        exit;
                    }
                    echo '<script>window.location.href="' . esc_url( $redirect ) . '";</script>';
                    return;
                }
            } elseif (!empty($starts) && !empty($ends) && count($starts) === count($ends) && !empty($dates)) {
                $today      = date('Y-m-d');
                $date_valid = true;
                foreach ($dates as $date) {
                    if ($date < $today) {
                        $date_valid = false;
                        break;
                    }
                }
                if ($date_valid) {
                    $ranges = [];
                    $valid  = true;
                    foreach ($starts as $idx => $start) {
                        $end = $ends[$idx] ?? '';
                        $mod = $modalities[$idx] ?? 'online';
                        if (!$start || !$end) {
                            continue;
                        }
                        $startTs = strtotime($start);
                        $endTs   = strtotime($end);
                        if ($startTs === false || $endTs === false) {
                            $valid = false;
                            break;
                        }
                        if ($endTs <= $startTs || ($endTs - $startTs) >= DAY_IN_SECONDS) {
                            $valid = false;
                            break;
                        }
                        $ranges[] = ['start' => $start, 'end' => $end, 'modality' => $mod];
                    }
                    if ($valid) {
                        usort($ranges, function ($a, $b) {
                            return strcmp($a['start'], $b['start']);
                        });
                        for ($i = 1; $i < count($ranges); $i++) {
                            if ($ranges[$i]['start'] < $ranges[$i - 1]['end']) {
                                $valid = false;
                                break;
                            }
                        }
                    }
                    if ($valid) {
                        $madridTz = new \DateTimeZone('Europe/Madrid');
                        $conflict_msgs = [];
                        foreach ($dates as $date) {
                            $busy_events = CalendarService::get_busy_calendar_events($tutor_id, $date, $date);
                            foreach ($busy_events as $ev) {
                                if (isset($ev->start->dateTime) && isset($ev->end->dateTime)) {
                                    $start = new \DateTime($ev->start->dateTime);
                                    $start->setTimezone($madridTz);
                                    $end = new \DateTime($ev->end->dateTime);
                                    $end->setTimezone($madridTz);
                                    $inRange = false;
                                    foreach ($ranges as $range) {
                                        if ($range['start'] <= $start->format('H:i') && $range['end'] >= $end->format('H:i')) {
                                            $inRange = true;
                                            break;
                                        }
                                    }
                                    if (!$inRange) {
                                        $conflict_msgs[] = sprintf(
                                            'La cita "%s" el %s de %s a %s queda fuera de los tramos seleccionados.',
                                            $ev->summary ?? '',
                                            $start->format('Y-m-d'),
                                            $start->format('H:i'),
                                            $end->format('H:i')
                                        );
                                    }
                                }
                            }
                        }
                        if (!empty($conflict_msgs)) {
                            foreach ($conflict_msgs as $cmsg) {
                                $messages[] = ['type' => 'error', 'text' => $cmsg];
                            }
                        } else {
                            $utcTz    = new \DateTimeZone('UTC');
                            $creation_failed = false;
                            $any_created = false;
                            if ($editing_date) {
                                CalendarService::delete_available_events_for_date($tutor_id, $editing_date);
                            }
                            foreach ($dates as $date) {
                                foreach ($ranges as $range) {
                                    $startObj = new \DateTime($date . 'T' . $range['start'] . ':00', $madridTz);
                                    $endObj   = new \DateTime($date . 'T' . $range['end']   . ':00', $madridTz);

                                $dayStart = new \DateTime($date . 'T00:00:00', $madridTz);
                                $dayEnd   = new \DateTime($date . 'T23:59:59', $madridTz);
                                $hasDSTChange = count($madridTz->getTransitions($dayStart->getTimestamp(), $dayEnd->getTimestamp())) > 1;

                                $busy = CalendarService::get_busy_calendar_events($tutor_id, $startObj->format('Y-m-d'), $endObj->format('Y-m-d'));
                                $overlap = false;
                                foreach ($busy as $ev) {
                                    if (isset($ev->start->dateTime) && isset($ev->end->dateTime)) {
                                        $busyStart = new \DateTime($ev->start->dateTime);
                                        $busyStart->setTimezone($madridTz);
                                        $busyEnd   = new \DateTime($ev->end->dateTime);
                                        $busyEnd->setTimezone($madridTz);
                                        if ($busyStart < $endObj && $busyEnd > $startObj) {
                                            // Allow busy events entirely within the availability range
                                            if (!($busyStart >= $startObj && $busyEnd <= $endObj)) {
                                                $overlap = true;
                                                break;
                                            }
                                        }
                                    }
                                }
                                if ($overlap) {
                                    $messages[] = [
                                        'type' => 'error',
                                        'text' => sprintf('No se creó la disponibilidad para %s de %s a %s por solaparse con otro evento.', $date, $range['start'], $range['end'])
                                    ];
                                    continue;
                                }

                                if ($hasDSTChange) {
                                    $duration   = $endObj->getTimestamp() - $startObj->getTimestamp();
                                    $startUtcObj = clone $startObj;
                                    $startUtcObj->setTimezone($utcTz);
                                    $endUtcObj   = (clone $startUtcObj)->modify('+' . $duration . ' seconds');
                                    $start_dt    = $startUtcObj->format('Y-m-d\\TH:i:s');
                                    $end_dt      = $endUtcObj->format('Y-m-d\\TH:i:s');
                                } else {
                                    $start_dt = $startObj->setTimezone($utcTz)->format('Y-m-d\\TH:i:s');
                                    $end_dt   = $endObj->setTimezone($utcTz)->format('Y-m-d\\TH:i:s');
                                }

                                $summary  = 'DISPONIBLE';
                                if ($range['modality'] === 'online') {
                                    $summary .= ' ONLINE';
                                } elseif ($range['modality'] === 'presencial') {
                                    $summary .= ' PRESENCIAL';
                                }
                                $created = CalendarService::create_calendar_event(
                                    $tutor_id,
                                    $summary,
                                    '',
                                    $start_dt,
                                    $end_dt,
                                    [],
                                    $range['modality']
                                );
                                if (is_wp_error($created)) {
                                    error_log('TutoriasBooking: handle_assign_availability - Error al crear evento: ' . $created->get_error_message());
                                    $messages[] = [
                                        'type' => 'error',
                                        'text' => sprintf('Error al crear la disponibilidad para %s de %s a %s: %s', $date, $range['start'], $range['end'], $created->get_error_message())
                                    ];
                                    $creation_failed = true;
                                    break 2;
                                }
                                error_log('TutoriasBooking: handle_assign_availability - Evento creado correctamente: ' . ($created->id ?? 'sin ID'));
                                $any_created = true;
                            }
                        }
                        if ($creation_failed) {
                            foreach ($dates as $date) {
                                CalendarService::delete_available_events_for_date($tutor_id, $date);
                            }
                            if ($editing_date) {
                                foreach ($original_events as $ev) {
                                    if (isset($ev->start->dateTime) && isset($ev->end->dateTime)) {
                                        $start_dt = (new \DateTime($ev->start->dateTime))->setTimezone($utcTz)->format('Y-m-d\\TH:i:s');
                                        $end_dt   = (new \DateTime($ev->end->dateTime))->setTimezone($utcTz)->format('Y-m-d\\TH:i:s');
                                        $summary = $ev->summary ?? 'DISPONIBLE';
                                        $description = $ev->description ?? '';
                                        $restored = CalendarService::create_calendar_event($tutor_id, $summary, $description, $start_dt, $end_dt);
                                        if (is_wp_error($restored)) {
                                            error_log('TutoriasBooking: handle_assign_availability - Error al restaurar evento: ' . $restored->get_error_message());
                                        } else {
                                            error_log('TutoriasBooking: handle_assign_availability - Evento restaurado: ' . ($restored->id ?? 'sin ID'));
                                        }
                                    }
                                }
                            }
                            $msg = 'Error al crear los eventos de disponibilidad.';
                            if ($editing_date) {
                                $msg .= ' Se restauró la disponibilidad original.';
                            }
                            $messages[] = ['type' => 'error', 'text' => $msg];
                        } else {
                            if ($any_created) {
                                $messages[] = ['type' => 'success', 'text' => 'Disponibilidad asignada correctamente.'];
                                if ($editing_date) {
                                    $redirect = admin_url('admin.php?page=tb-tutores&action=tb_assign_availability&tutor_id=' .
                                    $tutor_id);
                                    if ( wp_safe_redirect( $redirect ) ) {
                                        exit;
                                    }
                                    echo '<script>window.location.href="' . esc_url( $redirect ) . '";</script>';
                                    return;
                                }
                            }
                          }
                      }
                    } else {
                      $messages[] = ['type' => 'error', 'text' => 'Los rangos de tiempo son inválidos o se solapan. Verifica que la hora final sea mayor que la inicial y que el rango no exceda las 24 horas.'];
                    }
                } else {
                    $messages[] = ['type' => 'error', 'text' => 'No se pueden asignar fechas anteriores a hoy.'];
                }
            } else {
                $messages[] = ['type' => 'error', 'text' => 'Todos los campos son obligatorios.'];
            }
            }

            $events      = CalendarService::get_available_calendar_events($tutor_id, $start_range, $end_range);
            $busy_events = CalendarService::get_busy_calendar_events($tutor_id, $start_range, $end_range);
            $availability_hash = md5(json_encode($events));
            $existing_dates = [];
            foreach (array_merge($events, $busy_events) as $ev) {
                if (isset($ev->start->dateTime) && isset($ev->end->dateTime)) {
                    $start = new \DateTime($ev->start->dateTime);
                    $start->setTimezone($madridTz);
                    $end = new \DateTime($ev->end->dateTime);
                    $end->setTimezone($madridTz);
                } elseif (isset($ev->start->date) && isset($ev->end->date)) {
                    $start = new \DateTime($ev->start->date, $madridTz);
                    $end   = new \DateTime($ev->end->date, $madridTz);
                    $end->modify('-1 day');
                } else {
                    continue;
                }

                $current = clone $start;
                while ($current <= $end) {
                    $existing_dates[] = $current->format('Y-m-d');
                    $current->modify('+1 day');
                }
            }
            $existing_dates = array_values(array_unique($existing_dates));

            $edit_ranges = [];
            if ($edit_date) {
                $day_events = CalendarService::get_available_calendar_events($tutor_id, $edit_date, $edit_date);
                foreach ($day_events as $ev) {
                    if (isset($ev->start->dateTime) && isset($ev->end->dateTime)) {
                        $start = new \DateTime($ev->start->dateTime);
                        $start->setTimezone($madridTz);
                        $end = new \DateTime($ev->end->dateTime);
                        $end->setTimezone($madridTz);
                        $edit_ranges[] = [
                            'start' => $start->format('H:i'),
                            'end'   => $end->format('H:i'),
                        ];
                    }
                }
                $existing_dates = array_values(array_diff($existing_dates, [$edit_date]));
            }
        }

        self::render_assign_availability($tutor, $messages, $existing_dates, $edit_date, $edit_ranges, $availability_hash);
    }

    private static function render_assign_availability($tutor, $messages, $existing_dates, $edit_date = '', $edit_ranges = [], $availability_hash = '') {
        ?>
        <div class="tb-admin-wrapper">
            <?php foreach ($messages as $msg): ?>
                <div class="<?php echo $msg['type'] === 'success' ? 'notice-success' : 'notice-error'; ?> notice is-dismissible tb-notice">
                    <p><?php echo esc_html($msg['text']); ?></p>
                </div>
            <?php endforeach; ?>

            <div class="tb-card">
                <h2>Asignar Disponibilidad a <?php echo esc_html($tutor->nombre ?? ''); ?></h2>
                <form method="POST">
                    <?php wp_nonce_field('tb_assign_availability_action', 'tb_assign_availability_nonce'); ?>
                    <input type="hidden" name="tb_availability_hash" value="<?php echo esc_attr($availability_hash); ?>">
                    <div id="tb-time-ranges">
                        <div class="tb-time-range">
                            <label>Inicio</label>
                            <input type="time" name="tb_start_time[]" required>
                            <label>Fin</label>
                            <input type="time" name="tb_end_time[]" required>
                            <label>Modalidad</label>
                            <select name="tb_modality[]" required>
                                <option value="online">Online</option>
                                <option value="presencial">Presencial</option>
                            </select>
                            <button type="button" class="tb-button tb-add-range">+</button>
                        </div>
                    </div>
                    <div id="tb-calendar"></div>
                    <ul id="tb-selected-dates"></ul>
                    <div id="tb-hidden-dates"></div>
                    <?php if ($edit_date): ?>
                        <input type="hidden" name="tb_editing_date" value="<?php echo esc_attr($edit_date); ?>">
                    <?php endif; ?>
                    <button type="submit" name="tb_assign_availability" class="tb-button">Guardar</button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=tb-tutores')); ?>" class="tb-button">Volver</a>
                </form>
            </div>
        </div>
        <script>
            var tbExistingAvailabilityDates = <?php echo wp_json_encode($existing_dates); ?>;
            var tbEditingDate = <?php echo $edit_date ? "'" . esc_js($edit_date) . "'" : 'null'; ?>;
            var tbEditingRanges = <?php echo wp_json_encode($edit_ranges); ?>;
            var tbTutorId = <?php echo (int)($tutor->id ?? 0); ?>;
        </script>
        <?php
    }

    private static function import_tutores_from_xlsx($file_path) {
        global $wpdb;
        $rows = self::parse_xlsx($file_path);
        if (empty($rows)) {
            return 0;
        }
        $count = 0;
        array_shift($rows); // skip header
        foreach ($rows as $data) {
            $nombre = isset($data[0]) ? sanitize_text_field($data[0]) : '';
            $email  = isset($data[1]) ? sanitize_email($data[1]) : '';
            if (empty($nombre) || empty($email)) {
                continue;
            }
            $wpdb->insert(
                $wpdb->prefix . 'tutores',
                [
                    'nombre'      => $nombre,
                    'email'       => $email,
                    'calendar_id' => $email
                ]
            );
            $count++;
        }
        return $count;
    }

    private static function import_alumnos_from_xlsx($file_path, $table) {
        global $wpdb;
        $rows = self::parse_xlsx($file_path);
        if (empty($rows)) {
            return 0;
        }
        $count = 0;
        array_shift($rows); // skip header
        foreach ($rows as $data) {
            $dni        = isset($data[0]) ? sanitize_text_field($data[0]) : '';
            $nombre     = isset($data[1]) ? sanitize_text_field($data[1]) : '';
            $apellido   = isset($data[2]) ? sanitize_text_field($data[2]) : '';
            $email      = isset($data[3]) ? sanitize_email($data[3]) : '';
            $online = isset($data[4]) && strcasecmp(trim($data[4]), 'si') === 0 ? 1 : 0;
            $presencial = isset($data[5]) && strcasecmp(trim($data[5]), 'si') === 0 ? 1 : 0;
            $online     = $online ? 1 : 0;
            $presencial = $presencial ? 1 : 0;
            if (empty($dni) || empty($nombre) || empty($apellido) || empty($email)) {
                continue;
            }
            $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE dni = %s", $dni));
            if ($existing > 0) {
                continue;
            }
            $wpdb->insert(
                $table,
                [
                    'dni'        => $dni,
                    'email'      => $email,
                    'nombre'     => $nombre,
                    'apellido'   => $apellido,
                    'online'     => $online,
                    'presencial' => $presencial
                ]
            );
            $count++;
        }
        return $count;
    }

    private static function parse_xlsx($file_path) {
        $rows = [];
        $zip = new \ZipArchive();
        if ($zip->open($file_path) !== true) {
            return $rows;
        }
        $sharedStrings = [];
        if (($data = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $xml = simplexml_load_string($data);
            foreach ($xml->si as $si) {
                $sharedStrings[] = (string) $si->t;
            }
        }
        if (($sheet = $zip->getFromName('xl/worksheets/sheet1.xml')) !== false) {
            $xml = simplexml_load_string($sheet);
            foreach ($xml->sheetData->row as $row) {
                $cells = [];
                foreach ($row->c as $c) {
                    $v = (string) $c->v;
                    if ((string) $c['t'] === 's') {
                        $v = $sharedStrings[(int) $v] ?? '';
                    }
                    $cells[] = $v;
                }
                $rows[] = $cells;
            }
        }
        $zip->close();
        return $rows;
    }

}

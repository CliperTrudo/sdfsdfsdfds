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

        if (isset($_POST['tb_import_tutores']) && !empty($_FILES['tb_tutores_file']['tmp_name'])) {
            $imported  = self::import_tutores_from_xlsx($_FILES['tb_tutores_file']['tmp_name']);
            $messages[] = ['type' => 'success', 'text' => 'Se importaron ' . $imported . ' tutores.'];
        }

        if ($table_exists && isset($_POST['tb_import_alumnos']) && !empty($_FILES['tb_alumnos_file']['tmp_name'])) {
            $imported  = self::import_alumnos_from_xlsx($_FILES['tb_alumnos_file']['tmp_name'], $alumnos_reserva_table);
            $messages[] = ['type' => 'success', 'text' => 'Se importaron ' . $imported . ' alumnos de reserva.'];
        }

        if ($table_exists && isset($_POST['tb_reset_cita_id'])) {
            $alumno_id   = intval($_POST['tb_reset_cita_id']);
            $alumno_data = $wpdb->get_row($wpdb->prepare("SELECT dni FROM {$alumnos_reserva_table} WHERE id = %d", $alumno_id));
            if ($alumno_data) {
                CalendarService::delete_events_by_dni($alumno_data->dni);
            }

            $updated = $wpdb->update(
                $alumnos_reserva_table,
                ['tiene_cita' => 0],
                ['id' => $alumno_id],
                ['%d'],
                ['%d']
            );
            if ($updated !== false) {
                $messages[] = ['type' => 'success', 'text' => 'La reserva del alumno con ID ' . esc_html($alumno_id) . ' ha sido eliminada y el campo "Tiene Cita" se ha establecido en 0.'];
            } else {
                $messages[] = ['type' => 'error', 'text' => 'Error al actualizar el campo "Tiene Cita".'];
            }
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
                            'tiene_cita' => 0
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

        $tutores = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tutores");
        $alumnos_reserva = [];
        if ($table_exists) {
            $alumnos_reserva = $wpdb->get_results("SELECT id, dni, nombre, apellido, email, tiene_cita FROM {$alumnos_reserva_table}");
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
            $existing_slots = [];
            self::render_assign_availability($tutor, $messages, $existing_dates, $existing_slots);
            return;
        }

        if (isset($_POST['tb_assign_availability'])) {
            $starts = isset($_POST['tb_start_time']) ? array_map('sanitize_text_field', (array)$_POST['tb_start_time']) : [];
            $ends   = isset($_POST['tb_end_time']) ? array_map('sanitize_text_field', (array)$_POST['tb_end_time']) : [];
            $dates  = isset($_POST['tb_dates']) ? array_map('sanitize_text_field', (array)$_POST['tb_dates']) : [];

            $slots = [];
            foreach ($starts as $idx => $st) {
                $en = $ends[$idx] ?? '';
                if ($st && $en) {
                    $slots[] = [$st, $en];
                }
            }

            if (!empty($dates) && !empty($slots)) {
                foreach ($dates as $date) {
                    CalendarService::delete_available_events_by_date($tutor_id, $date);
                    foreach ($slots as $slot) {
                        $start_dt = $date . 'T' . $slot[0] . ':00';
                        $end_dt   = $date . 'T' . $slot[1] . ':00';
                        CalendarService::create_calendar_event($tutor_id, 'DISPONIBLE', '', $start_dt, $end_dt);
                    }
                }
                $messages[] = ['type' => 'success', 'text' => 'Disponibilidad asignada correctamente.'];
            } else {
                $messages[] = ['type' => 'error', 'text' => 'Todos los campos son obligatorios.'];
            }
        }

        $start_range = date('Y-m-d');
        $end_range = date('Y-m-d', strtotime('+6 months'));
        $events = CalendarService::get_available_calendar_events($tutor_id, $start_range, $end_range);
        $existing_dates = [];
        $existing_slots = [];
        foreach ($events as $ev) {
            if (isset($ev->start->dateTime)) {
                $date = date('Y-m-d', strtotime($ev->start->dateTime));
                $existing_dates[] = $date;
                $existing_slots[$date][] = date('H:i', strtotime($ev->start->dateTime)) . '-' . date('H:i', strtotime($ev->end->dateTime));
            }
        }
        $existing_dates = array_values(array_unique($existing_dates));
        $existing_slots = array_map('array_values', $existing_slots);

        self::render_assign_availability($tutor, $messages, $existing_dates, $existing_slots);
    }

    private static function render_assign_availability($tutor, $messages, $existing_dates, $existing_slots) {
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
                    <div id="tb-time-slots">
                        <div class="tb-time-slot">
                            <label>Inicio</label>
                            <input type="time" name="tb_start_time[]" required>
                            <label>Fin</label>
                            <input type="time" name="tb_end_time[]" required>
                            <button type="button" class="tb-button tb-add-slot">+</button>
                        </div>
                    </div>
                    <div id="tb-calendar"></div>
                    <ul id="tb-selected-dates"></ul>
                    <div id="tb-hidden-dates"></div>
                    <button type="submit" name="tb_assign_availability" class="tb-button">Guardar</button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=tb-tutores')); ?>" class="tb-button">Volver</a>
                </form>
            </div>
        </div>
        <script>
            window.tbExistingAvailabilityDates = <?php echo wp_json_encode($existing_dates); ?>;
            window.tbExistingAvailabilitySlots = <?php echo wp_json_encode($existing_slots); ?>;
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
            $dni      = isset($data[0]) ? sanitize_text_field($data[0]) : '';
            $nombre   = isset($data[1]) ? sanitize_text_field($data[1]) : '';
            $apellido = isset($data[2]) ? sanitize_text_field($data[2]) : '';
            $email    = isset($data[3]) ? sanitize_email($data[3]) : '';
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
                    'tiene_cita' => 0
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

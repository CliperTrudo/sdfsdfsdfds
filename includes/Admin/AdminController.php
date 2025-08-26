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
            self::render_assign_availability($tutor, $messages, $existing_dates);
            return;
        }

        if (isset($_POST['tb_assign_availability'])) {
            $start = sanitize_text_field($_POST['tb_start_time'] ?? '');
            $end   = sanitize_text_field($_POST['tb_end_time'] ?? '');
            $dates = isset($_POST['tb_dates']) ? array_map('sanitize_text_field', (array)$_POST['tb_dates']) : [];
            if ($start && $end && !empty($dates)) {
                foreach ($dates as $date) {
                    $start_dt = $date . 'T' . $start . ':00';
                    $end_dt   = $date . 'T' . $end . ':00';
                    CalendarService::create_calendar_event($tutor_id, 'DISPONIBLE', '', $start_dt, $end_dt);
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
        foreach ($events as $ev) {
            if (isset($ev->start->dateTime)) {
                $existing_dates[] = date('Y-m-d', strtotime($ev->start->dateTime));
            }
        }
        $existing_dates = array_values(array_unique($existing_dates));

        self::render_assign_availability($tutor, $messages, $existing_dates);
    }

    private static function render_assign_availability($tutor, $messages, $existing_dates) {
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
                    <label for="tb-start">Inicio</label>
                    <input id="tb-start" type="time" name="tb_start_time" required>
                    <label for="tb-end">Fin</label>
                    <input id="tb-end" type="time" name="tb_end_time" required>
                    <div id="tb-calendar"></div>
                    <ul id="tb-selected-dates"></ul>
                    <div id="tb-hidden-dates"></div>
                    <button type="submit" name="tb_assign_availability" class="tb-button">Guardar</button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=tb-tutores')); ?>" class="tb-button">Volver</a>
                </form>
            </div>
        </div>
        <script>
            var tbExistingAvailabilityDates = <?php echo wp_json_encode($existing_dates); ?>;
            jQuery(function($){
                if (!$('#tb-calendar').length) {
                    return;
                }

                var existing = Array.isArray(window.tbExistingAvailabilityDates) ? window.tbExistingAvailabilityDates : [];
                var selected = [];
                var startDate = new Date();
                startDate.setHours(0,0,0,0);
                var endDate = new Date();
                endDate.setMonth(endDate.getMonth() + 3);
                endDate.setHours(0,0,0,0);
                var current = new Date(startDate.getFullYear(), startDate.getMonth(), 1);

                function formatDate(d) {
                    var month = '' + (d.getMonth() + 1);
                    var day = '' + d.getDate();
                    var year = d.getFullYear();
                    if (month.length < 2) month = '0' + month;
                    if (day.length < 2) day = '0' + day;
                    return [year, month, day].join('-');
                }

                function refreshSelected() {
                    var list = $('#tb-selected-dates').empty();
                    var hidden = $('#tb-hidden-dates').empty();
                    selected.sort();
                    selected.forEach(function(d){
                        list.append('<li>' + d + '</li>');
                        hidden.append('<input type="hidden" name="tb_dates[]" value="' + d + '">');
                    });
                }

                function renderCalendar(monthDate) {
                    var calendar = $('#tb-calendar');
                    var month = monthDate.getMonth();
                    var year = monthDate.getFullYear();
                    var monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                    var dayNames = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

                    var html = '<div class="tb-calendar-month">';
                    html += '<div class="tb-calendar-nav">';
                    var prevDisabled = (monthDate.getFullYear() === startDate.getFullYear() && monthDate.getMonth() <= startDate.getMonth());
                    var nextDisabled = (monthDate.getFullYear() === endDate.getFullYear() && monthDate.getMonth() >= endDate.getMonth());
                    html += '<button id="tb_prev_month" class="tb-nav-btn"' + (prevDisabled ? ' disabled' : '') + '>&lt;</button>';
                    html += '<span class="tb-calendar-month-name">' + monthNames[month] + ' ' + year + '</span>';
                    html += '<button id="tb_next_month" class="tb-nav-btn"' + (nextDisabled ? ' disabled' : '') + '>&gt;</button>';
                    html += '</div>';

                    html += '<div class="tb-calendar-week-day-names">';
                    dayNames.forEach(function(d){ html += '<div class="tb-calendar-week-day">' + d + '</div>'; });
                    html += '</div>';

                    html += '<div class="tb-calendar-days">';
                    var firstDayIndex = new Date(year, month, 1).getDay();
                    for (var i=0; i<firstDayIndex; i++) {
                        html += '<div class="tb-calendar-day tb-empty"></div>';
                    }
                    var daysInMonth = new Date(year, month + 1, 0).getDate();
                    for (var d=1; d<=daysInMonth; d++) {
                        var dateObj = new Date(year, month, d);
                        var dateStr = formatDate(dateObj);
                        var classes = 'tb-calendar-day';
                        if (dateObj < startDate || dateObj > endDate || existing.indexOf(dateStr) !== -1) {
                            classes += ' tb-day-unavailable';
                        } else {
                            classes += ' tb-day-available';
                            if (selected.indexOf(dateStr) !== -1) {
                                classes += ' tb-selected';
                            }
                        }
                        html += '<div class="' + classes + '" data-date="' + dateStr + '">' + d + '</div>';
                    }
                    html += '</div></div>';
                    calendar.html(html);
                }

                $('#tb-calendar').on('click', '.tb-calendar-day.tb-day-available', function(){
                    var date = $(this).data('date');
                    var idx = selected.indexOf(date);
                    if (idx > -1) {
                        selected.splice(idx,1);
                        $(this).removeClass('tb-selected');
                    } else {
                        selected.push(date);
                        $(this).addClass('tb-selected');
                    }
                    refreshSelected();
                });

                $('#tb-calendar').on('click', '#tb_prev_month', function(){
                    if ($(this).prop('disabled')) return;
                    current.setMonth(current.getMonth() - 1);
                    renderCalendar(current);
                });

                $('#tb-calendar').on('click', '#tb_next_month', function(){
                    if ($(this).prop('disabled')) return;
                    current.setMonth(current.getMonth() + 1);
                    renderCalendar(current);
                });

                renderCalendar(current);
                refreshSelected();
            });
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

<?php
namespace TutoriasBooking\Admin;

/**
 * Handles logic for the admin page and prepares data for the view.
 */
class AdminController {
    /**
     * Render the admin page.
     */
    public static function handle_page() {
        global $wpdb;

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
            $alumno_id = intval($_POST['tb_reset_cita_id']);
            $updated   = $wpdb->update(
                $alumnos_reserva_table,
                ['tiene_cita' => 0],
                ['id' => $alumno_id],
                ['%d'],
                ['%d']
            );
            if ($updated !== false) {
                $messages[] = ['type' => 'success', 'text' => 'El campo "Tiene Cita" del alumno con ID ' . esc_html($alumno_id) . ' se ha establecido en 0.'];
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

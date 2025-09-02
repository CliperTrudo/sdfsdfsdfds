<?php
namespace TutoriasBooking\Admin;

use WP_List_Table;
use TutoriasBooking\Google\CalendarService;
use WP_Error;

class AppointmentsController {
    public static function render_page() {
        if ( ! class_exists( '\\WP_List_Table' ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        }

        $appointments_table = new Appointments_List_Table();
        $appointments_table->prepare_items();

        include TB_PLUGIN_DIR . 'templates/admin/appointments-page.php';
    }

    /**
     * Handle the editing of an appointment.
     *
     * Expects POST fields: appointment_id, event_id, new_start, new_end, tutor_id
     * new_start and new_end are expected in 'Y-m-d\TH:i' (local timezone).
     *
     * @return array|WP_Error
     */
    public static function handle_edit() {
        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
            return new WP_Error( 'invalid_method', 'Invalid request method.' );
        }

        $appointment_id = isset( $_POST['appointment_id'] ) ? intval( $_POST['appointment_id'] ) : 0;
        $event_id       = isset( $_POST['event_id'] ) ? sanitize_text_field( $_POST['event_id'] ) : '';
        $new_start      = isset( $_POST['new_start'] ) ? sanitize_text_field( $_POST['new_start'] ) : '';
        $new_end        = isset( $_POST['new_end'] ) ? sanitize_text_field( $_POST['new_end'] ) : '';
        $tutor_id       = isset( $_POST['tutor_id'] ) ? intval( $_POST['tutor_id'] ) : 0;

        if ( ! $appointment_id || empty( $event_id ) || empty( $new_start ) || empty( $new_end ) || ! $tutor_id ) {
            return new WP_Error( 'missing_fields', 'Datos incompletos.' );
        }

        global $wpdb;

        $tutor = $wpdb->get_row( $wpdb->prepare( "SELECT email, calendar_id, nombre FROM {$wpdb->prefix}tutores WHERE id = %d", $tutor_id ) );
        if ( ! $tutor ) {
            return new WP_Error( 'invalid_tutor', 'Tutor no válido.' );
        }

        $appointment = $wpdb->get_row( $wpdb->prepare( "SELECT alumno_id FROM {$wpdb->prefix}tb_citas WHERE ID = %d", $appointment_id ) );
        if ( ! $appointment ) {
            return new WP_Error( 'invalid_appointment', 'Cita no válida.' );
        }

        $student = $wpdb->get_row( $wpdb->prepare( "SELECT email, nombre, apellido FROM {$wpdb->prefix}alumnos_reserva WHERE id = %d", $appointment->alumno_id ) );

        // Convert provided times from local Europe/Madrid to UTC
        $madrid_tz = new \DateTimeZone( 'Europe/Madrid' );
        $utc_tz    = new \DateTimeZone( 'UTC' );
        $start_dt  = new \DateTime( $new_start, $madrid_tz );
        $end_dt    = new \DateTime( $new_end, $madrid_tz );
        $start_dt->setTimezone( $utc_tz );
        $end_dt->setTimezone( $utc_tz );

        $updated_event = CalendarService::update_event( $event_id, $start_dt->format( 'c' ), $end_dt->format( 'c' ), $tutor->calendar_id );
        if ( is_wp_error( $updated_event ) ) {
            return $updated_event;
        }

        $wpdb->update(
            $wpdb->prefix . 'tb_citas',
            [
                'start'      => $new_start,
                'end'        => $new_end,
                'tutor_id'   => $tutor_id,
                'updated_at' => current_time( 'mysql' ),
            ],
            [ 'ID' => $appointment_id ],
            [ '%s', '%s', '%d', '%s' ],
            [ '%d' ]
        );

        if ( $student ) {
            $student_subject = 'Cita de tutoría actualizada';
            $student_message = sprintf(
                "Hola %s %s,\n\nTu cita ha sido actualizada.\nNueva fecha y hora: %s - %s\nTutor: %s\n",
                $student->nombre,
                $student->apellido,
                $new_start,
                $new_end,
                $tutor->nombre
            );
            wp_mail( $student->email, $student_subject, $student_message );
        }

        $tutor_subject = 'Cita de tutoría actualizada';
        $tutor_message = sprintf(
            "Se ha actualizado una cita.\nFecha y hora: %s - %s\nAlumno ID: %d",
            $new_start,
            $new_end,
            $appointment->alumno_id
        );
        wp_mail( $tutor->email, $tutor_subject, $tutor_message );

        return [ 'success' => true ];
    }
}

class Appointments_List_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct([
            'singular' => 'appointment',
            'plural'   => 'appointments',
            'ajax'     => false,
        ]);
    }

    public static function get_appointments( $per_page = 20, $page_number = 1 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'tb_citas';
        $sql   = "SELECT ID, tutor_id, alumno_id, inicio, fin, estado, event_id FROM $table";

        $orderby = ! empty( $_REQUEST['orderby'] ) ? esc_sql( $_REQUEST['orderby'] ) : 'ID';
        $order   = ! empty( $_REQUEST['order'] ) ? esc_sql( $_REQUEST['order'] ) : 'asc';
        $sql    .= " ORDER BY {$orderby} {$order}";
        $sql    .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $per_page, ( $page_number - 1 ) * $per_page );

        return $wpdb->get_results( $sql, ARRAY_A );
    }

    public static function record_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'tb_citas';
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
    }

    public function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [ $columns, $hidden, $sortable ];

        $per_page     = $this->get_items_per_page( 'appointments_per_page', 20 );
        $current_page = $this->get_pagenum();

        $this->items = self::get_appointments( $per_page, $current_page );
        $total_items = self::record_count();

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ]);
    }

    public function get_columns() {
        return [
            'ID'        => 'ID',
            'tutor_id'  => 'Tutor',
            'alumno_id' => 'Alumno',
            'inicio'    => 'Inicio',
            'fin'         => 'Fin',
            'estado'      => 'Estado',
            'attachments' => 'Adjuntos',
            'actions'     => 'Acciones',
        ];
    }

    protected function get_sortable_columns() {
        return [
            'ID'     => [ 'ID', true ],
            'inicio' => [ 'inicio', false ],
            'fin'    => [ 'fin', false ],
            'estado' => [ 'estado', false ],
        ];
    }

    public function column_default( $item, $column_name ) {
        return $item[ $column_name ];
    }

    public function column_actions( $item ) {
        $button = sprintf(
            '<button type="button" class="button tb-edit-appointment" data-id="%d" data-event="%s" data-start="%s" data-end="%s" data-tutor="%d">Editar</button>',
            intval( $item['ID'] ),
            esc_attr( $item['event_id'] ?? '' ),
            esc_attr( $item['inicio'] ),
            esc_attr( $item['fin'] ),
            intval( $item['tutor_id'] )
        );
        return $button;
    }

    public function column_attachments( $item ) {
        global $wpdb;
        $table = $wpdb->prefix . 'tb_cita_adjuntos';
        $attachments = $wpdb->get_results( $wpdb->prepare( "SELECT id, file_url FROM {$table} WHERE cita_id = %d", $item['ID'] ) );
        $html = '<div class="tb-attachments">';
        if ( $attachments ) {
            foreach ( $attachments as $att ) {
                $html .= sprintf(
                    '<div><a href="%1$s" target="_blank">%2$s</a> <button type="button" class="tb-delete-attachment" data-id="%3$d">Eliminar</button></div>',
                    esc_url( $att->file_url ),
                    esc_html( basename( $att->file_url ) ),
                    intval( $att->id )
                );
            }
        }
        $html .= sprintf( '<input type="file" class="tb-attachment-input" data-cita="%d" />', intval( $item['ID'] ) );
        $html .= '</div>';
        return $html;
    }
}

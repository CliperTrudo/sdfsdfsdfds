<?php
namespace TutoriasBooking\Admin;

use WP_List_Table;

class AppointmentsController {
    public static function render_page() {
        if ( ! class_exists( '\\WP_List_Table' ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        }

        $appointments_table = new Appointments_List_Table();
        $appointments_table->prepare_items();

        include TB_PLUGIN_DIR . 'templates/admin/appointments-page.php';
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
        $sql   = "SELECT ID, tutor_id, alumno_id, inicio, fin, estado FROM $table";

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
            'fin'       => 'Fin',
            'estado'    => 'Estado',
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
}

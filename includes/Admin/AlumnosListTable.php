<?php
namespace TutoriasBooking\Admin;

use function __;
use function absint;
use function add_query_arg;
use function admin_url;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_html_e;
use function esc_js;
use function esc_url;
use function sanitize_email;
use function sanitize_text_field;
use function sprintf;
use function strtoupper;
use function wp_nonce_field;
use function wp_nonce_url;
use function wp_unslash;
use function wp_verify_nonce;
use function _n;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class AlumnosListTable extends \WP_List_Table {
    /**
     * @var array<int, array<string, mixed>>
     */
    protected $items = [];

    /**
     * @var array<int, array{type:string,text:string}>
     */
    private $messages = [];

    /**
     * @var string
     */
    private $table_name;

    public function __construct(string $table_name) {
        $this->table_name = $table_name;

        parent::__construct([
            'singular' => 'alumno',
            'plural'   => 'alumnos',
            'ajax'     => false,
        ]);
    }

    public function prepare_items(): void {
        $this->process_bulk_action();

        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable, 'dni'];

        $per_page     = $this->get_items_per_page('tb_alumnos_per_page', 20);
        $current_page = $this->get_pagenum();
        $search       = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : '';
        $orderby      = isset($_REQUEST['orderby']) ? sanitize_text_field(wp_unslash($_REQUEST['orderby'])) : 'id';
        $order        = isset($_REQUEST['order']) ? strtoupper(sanitize_text_field(wp_unslash($_REQUEST['order']))) : 'ASC';

        $allowed_orderby = ['id', 'dni', 'nombre', 'apellido', 'email', 'online', 'presencial'];
        if (!in_array($orderby, $allowed_orderby, true)) {
            $orderby = 'id';
        }
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'ASC';
        }

        global $wpdb;

        $where_clauses = [];
        $prepare_args  = [];

        if ($search !== '') {
            $like            = '%' . $wpdb->esc_like($search) . '%';
            $where_clauses[] = '(dni LIKE %s OR nombre LIKE %s OR apellido LIKE %s OR email LIKE %s)';
            $prepare_args[]  = $like;
            $prepare_args[]  = $like;
            $prepare_args[]  = $like;
            $prepare_args[]  = $like;
        }

        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = ' WHERE ' . implode(' AND ', $where_clauses);
        }

        $count_query = "SELECT COUNT(*) FROM {$this->table_name}{$where_sql}";
        if (!empty($prepare_args)) {
            $total_items = (int) $wpdb->get_var($wpdb->prepare($count_query, $prepare_args));
        } else {
            $total_items = (int) $wpdb->get_var($count_query);
        }

        $data_query   = "SELECT id, dni, nombre, apellido, email, online, presencial FROM {$this->table_name}{$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $data_args    = $prepare_args;
        $data_args[]  = $per_page;
        $data_args[]  = ($current_page - 1) * $per_page;
        $prepared_sql = $wpdb->prepare($data_query, $data_args);
        $results      = $wpdb->get_results($prepared_sql, ARRAY_A);

        $items = [];
        if (is_array($results)) {
            foreach ($results as $row) {
                $items[] = $this->sanitize_item($row);
            }
        }

        $this->items = $items;

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => $per_page > 0 ? (int) ceil($total_items / $per_page) : 0,
        ]);
    }

    public function get_columns(): array {
        return [
            'cb'         => '<input type="checkbox" />',
            'id'         => __('ID', 'tutorias-booking'),
            'dni'        => __('DNI', 'tutorias-booking'),
            'nombre'     => __('Nombre', 'tutorias-booking'),
            'apellido'   => __('Apellido', 'tutorias-booking'),
            'email'      => __('Email', 'tutorias-booking'),
            'online'     => __('Online', 'tutorias-booking'),
            'presencial' => __('Presencial', 'tutorias-booking'),
            'acciones'   => __('Acciones', 'tutorias-booking'),
        ];
    }

    protected function get_sortable_columns(): array {
        return [
            'id'       => ['id', true],
            'dni'      => ['dni', false],
            'nombre'   => ['nombre', false],
            'apellido' => ['apellido', false],
            'email'    => ['email', false],
        ];
    }

    protected function column_cb($item): string {
        return sprintf('<input type="checkbox" name="alumno[]" value="%d" />', $item['id']);
    }

    protected function column_dni($item): string {
        $alumno_id = (int) $item['id'];

        $delete_url = wp_nonce_url(
            add_query_arg([
                'page'   => 'tb-alumnos',
                'action' => 'delete',
                'alumno' => $alumno_id,
            ], admin_url('admin.php')),
            'tb_delete_alumno_' . $alumno_id
        );

        $actions = [
            'delete' => sprintf('<a href="%s" onclick="return confirm(\'%s\');">%s</a>', esc_url($delete_url), esc_js(__('¿Eliminar este alumno?', 'tutorias-booking')), esc_html__('Eliminar', 'tutorias-booking')),
        ];

        return sprintf('<strong>%s</strong>%s', esc_html($item['dni']), $this->row_actions($actions));
    }

    protected function column_online($item): string {
        $alumno_id = (int) $item['id'];
        $checked   = !empty($item['online']) ? 'checked' : '';
        $label     = sprintf(
            __('Estado online para %s %s', 'tutorias-booking'),
            $item['nombre'],
            $item['apellido']
        );

        return sprintf(
            '<label class="screen-reader-text" for="tb_online_%1$d">%2$s</label><input type="checkbox" id="tb_online_%1$d" name="tb_online" value="1" form="tb_update_%1$d" %3$s />',
            $alumno_id,
            esc_html($label),
            $checked
        );
    }

    protected function column_presencial($item): string {
        $alumno_id = (int) $item['id'];
        $checked   = !empty($item['presencial']) ? 'checked' : '';
        $label     = sprintf(
            __('Estado presencial para %s %s', 'tutorias-booking'),
            $item['nombre'],
            $item['apellido']
        );

        return sprintf(
            '<label class="screen-reader-text" for="tb_presencial_%1$d">%2$s</label><input type="checkbox" id="tb_presencial_%1$d" name="tb_presencial" value="1" form="tb_update_%1$d" %3$s />',
            $alumno_id,
            esc_html($label),
            $checked
        );
    }

    protected function column_acciones($item): string {
        $alumno_id = (int) $item['id'];

        $update_form  = '<form method="POST" id="tb_update_' . esc_attr((string) $alumno_id) . '" class="tb-inline-form">';
        $update_form .= wp_nonce_field('tb_admin_action', 'tb_admin_nonce', true, false);
        $update_form .= '<input type="hidden" name="tb_update_alumno_id" value="' . esc_attr((string) $alumno_id) . '" />';
        $update_form .= '<button type="submit" class="tb-button">' . esc_html__('Actualizar', 'tutorias-booking') . '</button>';
        $update_form .= '</form>';

        return $update_form;
    }

    public function column_default($item, $column_name): string {
        if (isset($item[$column_name])) {
            return esc_html((string) $item[$column_name]);
        }

        return '';
    }

    protected function get_bulk_actions(): array {
        return [
            'delete' => esc_html__('Eliminar', 'tutorias-booking'),
        ];
    }

    public function no_items(): void {
        esc_html_e('No hay alumnos registrados.', 'tutorias-booking');
    }

    public function get_messages(): array {
        return $this->messages;
    }

    protected function process_bulk_action(): void {
        $action = $this->current_action();
        if ('delete' !== $action) {
            return;
        }

        $ids = [];
        if (isset($_REQUEST['alumno'])) {
            $raw_ids = $_REQUEST['alumno'];
            if (!is_array($raw_ids)) {
                $raw_ids = [$raw_ids];
            }
            foreach ($raw_ids as $id) {
                $ids[] = absint($id);
            }
        }

        $ids = array_values(array_filter($ids));
        if (empty($ids)) {
            return;
        }

        $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        $nonce_action = count($ids) > 1 ? 'bulk-' . $this->_args['plural'] : 'tb_delete_alumno_' . $ids[0];
        if (!wp_verify_nonce($nonce, $nonce_action)) {
            $this->messages[] = [
                'type' => 'error',
                'text' => __('No se pudo verificar la petición para eliminar alumnos.', 'tutorias-booking'),
            ];
            return;
        }

        global $wpdb;
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $delete_sql   = "DELETE FROM {$this->table_name} WHERE id IN ($placeholders)";
        $result       = $wpdb->query($wpdb->prepare($delete_sql, $ids));

        if ($result === false) {
            $this->messages[] = [
                'type' => 'error',
                'text' => __('Ocurrió un error al eliminar los alumnos seleccionados.', 'tutorias-booking'),
            ];
        } else {
            $count = (int) $result;
            $this->messages[] = [
                'type' => 'success',
                'text' => sprintf(_n('%d alumno eliminado.', '%d alumnos eliminados.', $count, 'tutorias-booking'), $count),
            ];
        }
    }

    private function sanitize_item(array $row): array {
        return [
            'id'         => isset($row['id']) ? absint($row['id']) : 0,
            'dni'        => isset($row['dni']) ? sanitize_text_field($row['dni']) : '',
            'nombre'     => isset($row['nombre']) ? sanitize_text_field($row['nombre']) : '',
            'apellido'   => isset($row['apellido']) ? sanitize_text_field($row['apellido']) : '',
            'email'      => isset($row['email']) ? sanitize_email($row['email']) : '',
            'online'     => !empty($row['online']) ? 1 : 0,
            'presencial' => !empty($row['presencial']) ? 1 : 0,
        ];
    }
}

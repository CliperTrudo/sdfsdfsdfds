<?php
namespace TutoriasBooking\Admin;

use function __;
use function absint;
use function add_query_arg;
use function admin_url;
use function esc_html;
use function esc_html__;
use function esc_html_e;
use function esc_js;
use function esc_url;
use function sanitize_email;
use function sanitize_text_field;
use function sprintf;
use function strtoupper;
use function wp_nonce_url;
use function wp_unslash;
use function wp_verify_nonce;
use function _n;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class TutorsListTable extends \WP_List_Table {
    /**
     * @var array<int, array<string, mixed>>
     */
    protected $items = [];

    /**
     * @var array<int, array{type:string,text:string}>
     */
    private $messages = [];

    public function __construct() {
        parent::__construct([
            'singular' => 'tutor',
            'plural'   => 'tutores',
            'ajax'     => false,
        ]);
    }

    /**
     * Prepare table items, process actions, and setup pagination.
     */
    public function prepare_items(): void {
        $this->process_bulk_action();

        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable, 'nombre'];

        $per_page     = $this->get_items_per_page('tb_tutores_per_page', 20);
        $current_page = $this->get_pagenum();
        $search       = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : '';
        $orderby      = isset($_REQUEST['orderby']) ? sanitize_text_field(wp_unslash($_REQUEST['orderby'])) : 'nombre';
        $order        = isset($_REQUEST['order']) ? strtoupper(sanitize_text_field(wp_unslash($_REQUEST['order']))) : 'ASC';

        $allowed_orderby = ['nombre', 'email'];
        if (!in_array($orderby, $allowed_orderby, true)) {
            $orderby = 'nombre';
        }
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'ASC';
        }

        global $wpdb;
        $table = $wpdb->prefix . 'tutores';

        $where_clauses = [];
        $prepare_args  = [];

        if ($search !== '') {
            $like            = '%' . $wpdb->esc_like($search) . '%';
            $where_clauses[] = '(nombre LIKE %s OR email LIKE %s)';
            $prepare_args[]  = $like;
            $prepare_args[]  = $like;
        }

        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = ' WHERE ' . implode(' AND ', $where_clauses);
        }

        $count_query = "SELECT COUNT(*) FROM {$table}{$where_sql}";
        if (!empty($prepare_args)) {
            $total_items = (int) $wpdb->get_var($wpdb->prepare($count_query, $prepare_args));
        } else {
            $total_items = (int) $wpdb->get_var($count_query);
        }

        $data_query   = "SELECT id, nombre, email FROM {$table}{$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $data_args    = $prepare_args;
        $data_args[]  = $per_page;
        $data_args[]  = ($current_page - 1) * $per_page;
        $prepared_sql = $wpdb->prepare($data_query, $data_args);
        $results      = $wpdb->get_results($prepared_sql, ARRAY_A);

        $tokens_rows = $wpdb->get_col("SELECT DISTINCT tutor_id FROM {$wpdb->prefix}tutores_tokens");
        $tokens      = [];
        if (is_array($tokens_rows)) {
            foreach ($tokens_rows as $token_id) {
                $tokens[(int) $token_id] = true;
            }
        }

        $items = [];
        if (is_array($results)) {
            foreach ($results as $row) {
                $item = $this->sanitize_item($row);
                $has_token = !empty($tokens[$item['id']]);
                $item['estado']       = $has_token;
                $item['estado_label'] = $has_token ? '✅ Conectado' : '❌ No conectado';
                $items[] = $item;
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
            'cb'     => '<input type="checkbox" />',
            'nombre' => __('Nombre', 'tutorias-booking'),
            'email'  => __('Email', 'tutorias-booking'),
            'estado' => __('Estado', 'tutorias-booking'),
        ];
    }

    protected function get_sortable_columns(): array {
        return [
            'nombre' => ['nombre', true],
            'email'  => ['email', false],
        ];
    }

    protected function column_cb($item): string {
        return sprintf('<input type="checkbox" name="tutor[]" value="%d" />', $item['id']);
    }

    protected function column_nombre($item): string {
        $tutor_id = (int) $item['id'];

        $connect_url = add_query_arg([
            'page'     => 'tb-tutores',
            'action'   => 'tb_auth_google',
            'tutor_id' => $tutor_id,
        ], admin_url('admin.php'));

        $availability_url = add_query_arg([
            'page'     => 'tb-tutores',
            'action'   => 'tb_assign_availability',
            'tutor_id' => $tutor_id,
        ], admin_url('admin.php'));

        $delete_url = wp_nonce_url(
            add_query_arg([
                'page'   => 'tb-tutores',
                'action' => 'delete',
                'tutor'  => $tutor_id,
            ], admin_url('admin.php')),
            'tb_delete_tutor_' . $tutor_id
        );

        $actions = [
            'connect'       => sprintf('<a href="%s">%s</a>', esc_url($connect_url), esc_html__('Conectar Calendar', 'tutorias-booking')),
            'availability'  => sprintf('<a href="%s">%s</a>', esc_url($availability_url), esc_html__('Asignar Disponibilidad', 'tutorias-booking')),
            'delete'        => sprintf('<a href="%s" onclick="return confirm(\'%s\');">%s</a>', esc_url($delete_url), esc_js(__('¿Eliminar este tutor?', 'tutorias-booking')), esc_html__('Eliminar', 'tutorias-booking')),
        ];

        return sprintf('<strong>%s</strong>%s', esc_html($item['nombre']), $this->row_actions($actions));
    }

    protected function column_email($item): string {
        return sprintf('<a href="mailto:%1$s">%1$s</a>', esc_html($item['email']));
    }

    protected function column_estado($item): string {
        return esc_html($item['estado_label']);
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
        esc_html_e('No hay tutores registrados.', 'tutorias-booking');
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
        if (isset($_REQUEST['tutor'])) {
            $raw_ids = $_REQUEST['tutor'];
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
        $nonce_action = count($ids) > 1 ? 'bulk-' . $this->_args['plural'] : 'tb_delete_tutor_' . $ids[0];
        if (!wp_verify_nonce($nonce, $nonce_action)) {
            $this->messages[] = [
                'type' => 'error',
                'text' => __('No se pudo verificar la petición para eliminar tutores.', 'tutorias-booking'),
            ];
            return;
        }

        global $wpdb;
        $table       = $wpdb->prefix . 'tutores';
        $tokens_table = $wpdb->prefix . 'tutores_tokens';

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $delete_sql   = "DELETE FROM {$table} WHERE id IN ($placeholders)";
        $result       = $wpdb->query($wpdb->prepare($delete_sql, $ids));

        $wpdb->query($wpdb->prepare("DELETE FROM {$tokens_table} WHERE tutor_id IN ($placeholders)", $ids));

        if ($result === false) {
            $this->messages[] = [
                'type' => 'error',
                'text' => __('Ocurrió un error al eliminar los tutores seleccionados.', 'tutorias-booking'),
            ];
        } else {
            $count = (int) $result;
            $this->messages[] = [
                'type' => 'success',
                'text' => sprintf(_n('%d tutor eliminado.', '%d tutores eliminados.', $count, 'tutorias-booking'), $count),
            ];
        }
    }

    private function sanitize_item(array $row): array {
        return [
            'id'     => isset($row['id']) ? absint($row['id']) : 0,
            'nombre' => isset($row['nombre']) ? sanitize_text_field($row['nombre']) : '',
            'email'  => isset($row['email']) ? sanitize_email($row['email']) : '',
        ];
    }
}

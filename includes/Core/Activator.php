<?php
namespace TutoriasBooking\Core;

class Activator {
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql_tutores = "CREATE TABLE {$wpdb->prefix}tutores (
            id INT AUTO_INCREMENT,
            nombre VARCHAR(100),
            email VARCHAR(100),
            calendar_id VARCHAR(100),
            PRIMARY KEY (id)
        ) {$charset_collate};";
        dbDelta($sql_tutores);

        $sql_tokens = "CREATE TABLE {$wpdb->prefix}tutores_tokens (
            tutor_id INT NOT NULL,
            access_token TEXT,
            refresh_token TEXT,
            expiry DATETIME,
            PRIMARY KEY (tutor_id)
        ) {$charset_collate};";
        dbDelta($sql_tokens);

        $sql_reserva = "CREATE TABLE {$wpdb->prefix}alumnos_reserva (
            id INT AUTO_INCREMENT,
            dni VARCHAR(20) NOT NULL,
            email VARCHAR(100) NOT NULL,
            nombre VARCHAR(100),
            apellido VARCHAR(100),
            online TINYINT(1) DEFAULT 0,
            presencial TINYINT(1) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY unique_dni (dni)
        ) {$charset_collate};";
        dbDelta($sql_reserva);
    }

    /**
     * Upgrade routine to replace `tiene_cita` with `online` and `presencial` columns.
     * Preserves existing data by mapping previous `tiene_cita` values to `presencial`.
     */
    public static function maybe_upgrade() {
        global $wpdb;

        $installed = get_option('tutorias_booking_db_version', '1.0.0');
        $target    = '1.1.0';

        if (version_compare($installed, $target, '>=')) {
            return;
        }

        $table = $wpdb->prefix . 'alumnos_reserva';

        // Add new columns if they do not exist
        if ($wpdb->get_var("SHOW COLUMNS FROM {$table} LIKE 'online'") === null) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN online TINYINT(1) DEFAULT 0");
        }
        if ($wpdb->get_var("SHOW COLUMNS FROM {$table} LIKE 'presencial'") === null) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN presencial TINYINT(1) DEFAULT 0");
        }

        // Migrate existing data from `tiene_cita` to `presencial`
        if ($wpdb->get_var("SHOW COLUMNS FROM {$table} LIKE 'tiene_cita'") !== null) {
            $wpdb->query("UPDATE {$table} SET presencial = tiene_cita");
            $wpdb->query("ALTER TABLE {$table} DROP COLUMN tiene_cita");
        }

        update_option('tutorias_booking_db_version', $target);
    }
}

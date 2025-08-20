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
            tiene_cita TINYINT(1) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY unique_dni (dni)
        ) {$charset_collate};";
        dbDelta($sql_reserva);
    }
}

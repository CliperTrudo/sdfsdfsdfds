<?php
/*
Plugin Name: Tutorías – Reserva de Exámenes
Description: Gestión de reservas de tutorías con integración en Google Calendar y Meet.
Version: 1.0.3
Author: Equipo IT de Versus
Requires at least: 6.0
Requires PHP: 7.4

GitHub Plugin URI: salflet/tutorias-booking
Update URI: https://github.com/salflet/tutorias-booking
Primary Branch: master
Release Asset: true
*/


// Suprime deprecated warnings
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
@ini_set('display_errors', 0);

// Define constantes para rutas y URL del plugin
if ( ! defined( 'TB_PLUGIN_FILE' ) ) {
    define( 'TB_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'TB_PLUGIN_URL' ) ) {
    define( 'TB_PLUGIN_URL', plugin_dir_url( TB_PLUGIN_FILE ) );
}
if ( ! defined( 'TB_PLUGIN_DIR' ) ) {
    define( 'TB_PLUGIN_DIR', plugin_dir_path( TB_PLUGIN_FILE ) );
}

// Composer autoload
$composerAutoload = TB_PLUGIN_DIR . 'vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    add_action('admin_notices', function() {
        echo '<div class="error"><b>Falta vendor/autoload.php</b>: ejecuta <code>composer install</code> en el directorio raíz del plugin.</div>';
    });
    return;
}

// Incluye la clase de instalación para registrar el hook de activación.
// Asumiendo que 'install.php' está dentro de la carpeta 'includes'.
require_once TB_PLUGIN_DIR . 'includes/Core/Activator.php';
require_once TB_PLUGIN_DIR . 'includes/Core/Loader.php';

// Registra la función de activación del plugin.
register_activation_hook(__FILE__, ['TutoriasBooking\\Core\\Activator', 'activate']);

// Inicia la carga de todos los componentes del plugin.
TutoriasBooking\Core\Loader::init();

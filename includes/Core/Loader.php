<?php
namespace TutoriasBooking\Core;

class Loader {
    public static function init() {
        require_once TB_PLUGIN_DIR . 'includes/Admin/AdminMenu.php';
        require_once TB_PLUGIN_DIR . 'includes/Admin/AdminPage.php';
        require_once TB_PLUGIN_DIR . 'includes/Admin/AdminController.php';
        require_once TB_PLUGIN_DIR . 'includes/Admin/TutorsListTable.php';
        require_once TB_PLUGIN_DIR . 'includes/Admin/AlumnosListTable.php';
        require_once TB_PLUGIN_DIR . 'includes/Admin/AjaxHandlers.php';
        require_once TB_PLUGIN_DIR . 'includes/Frontend/Shortcodes.php';
        require_once TB_PLUGIN_DIR . 'includes/Frontend/AjaxHandlers.php';
        require_once TB_PLUGIN_DIR . 'includes/Google/GoogleClient.php';
        require_once TB_PLUGIN_DIR . 'includes/Google/CalendarService.php';

        \TutoriasBooking\Admin\AdminMenu::register();
        \TutoriasBooking\Admin\AjaxHandlers::init();
        \TutoriasBooking\Frontend\AjaxHandlers::init();
    }
}

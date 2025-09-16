<?php
namespace TutoriasBooking\Infrastructure;

class Loader {
    public static function init(): void {
        require_once dirname(__DIR__) . '/Admin/AdminPage.php';
        require_once dirname(__DIR__) . '/Frontend/Shortcodes.php';

        \TutoriasBooking\Admin\AdminMenu::register();
        \TutoriasBooking\Admin\AjaxHandlers::init();
        \TutoriasBooking\Frontend\AjaxHandlers::init();

        add_action('admin_init', ['TutoriasBooking\\Infrastructure\\Google\\GoogleClient', 'handle_oauth']);
        add_action('init', ['TutoriasBooking\\Infrastructure\\Google\\GoogleClient', 'handle_oauth']);
    }
}

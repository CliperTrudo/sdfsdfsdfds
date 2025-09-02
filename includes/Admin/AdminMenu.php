<?php
namespace TutoriasBooking\Admin;

class AdminMenu {
    public static function register() {
        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    public static function add_menu() {
        add_menu_page(
            'Tutorías',
            'Tutorías',
            'manage_options',
            'tb-tutores',
            '\\TutoriasBooking\\Admin\\tb_pagina_tutores'
        );

        add_submenu_page(
            'tb-tutores',
            'Citas',
            'Citas',
            'manage_options',
            'tb-citas',
            [AppointmentsList::class, 'render']
        );
    }

    public static function enqueue_assets($hook) {
        if (!in_array($hook, ['toplevel_page_tb-tutores', 'tb-tutores_page_tb-citas'], true)) {
            return;
        }
        $admin_css    = TB_PLUGIN_DIR . 'assets/css/admin.css';
        $frontend_css = TB_PLUGIN_DIR . 'assets/css/frontend.css';
        $admin_js     = TB_PLUGIN_DIR . 'assets/js/admin.js';

        wp_enqueue_style(
            'tb-admin',
            TB_PLUGIN_URL . 'assets/css/admin.css',
            [],
            file_exists($admin_css) ? filemtime($admin_css) : false
        );

        if (isset($_GET['action']) && $_GET['action'] === 'tb_assign_availability') {
            wp_enqueue_style(
                'tb-frontend',
                TB_PLUGIN_URL . 'assets/css/frontend.css',
                [],
                file_exists($frontend_css) ? filemtime($frontend_css) : false
            );
        }

        wp_enqueue_script(
            'tb-admin',
            TB_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            file_exists($admin_js) ? filemtime($admin_js) : false,
            true
        );

        wp_localize_script(
            'tb-admin',
            'tbAdminData',
            [
                'ajax_nonce' => wp_create_nonce('tb_get_day_availability'),
                'maxMonths'  => TB_MAX_MONTHS,
            ]
        );
    }
}

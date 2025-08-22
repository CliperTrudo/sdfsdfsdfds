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
    }

    public static function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_tb-tutores') {
            return;
        }
        wp_enqueue_style('tb-admin', TB_PLUGIN_URL . 'assets/css/admin.css');
        if (isset($_GET['action']) && $_GET['action'] === 'tb_assign_availability') {
            wp_enqueue_style('tb-frontend', TB_PLUGIN_URL . 'assets/css/frontend.css');
        }
        wp_enqueue_script('tb-admin', TB_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], false, true);
    }
}

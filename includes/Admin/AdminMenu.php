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
        $admin_css    = TB_PLUGIN_DIR . 'assets/css/admin.css';
        $frontend_css = TB_PLUGIN_DIR . 'assets/css/frontend.css';
        $admin_js     = TB_PLUGIN_DIR . 'assets/js/admin.js';
        $events_js    = TB_PLUGIN_DIR . 'assets/js/events.js';
        $utils_js     = TB_PLUGIN_DIR . 'assets/js/calendar-utils.js';
        $edit_js      = TB_PLUGIN_DIR . 'assets/js/admin-edit.js';

        wp_enqueue_style(
            'tb-frontend',
            TB_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            file_exists($frontend_css) ? filemtime($frontend_css) : false
        );

        wp_enqueue_style(
            'tb-admin',
            TB_PLUGIN_URL . 'assets/css/admin.css',
            [],
            file_exists($admin_css) ? filemtime($admin_css) : false
        );

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

        wp_enqueue_script(
            'tb-calendar-utils',
            TB_PLUGIN_URL . 'assets/js/calendar-utils.js',
            ['jquery'],
            file_exists($utils_js) ? filemtime($utils_js) : false,
            true
        );

        wp_enqueue_script(
            'tb-events',
            TB_PLUGIN_URL . 'assets/js/events.js',
            ['jquery','tb-calendar-utils'],
            file_exists($events_js) ? filemtime($events_js) : false,
            true
        );

        wp_localize_script(
            'tb-events',
            'tbEventsData',
            [
                'nonce' => wp_create_nonce('tb_events_nonce'),
            ]
        );

        wp_enqueue_script(
            'tb-admin-edit',
            TB_PLUGIN_URL . 'assets/js/admin-edit.js',
            ['jquery','tb-calendar-utils'],
            file_exists($edit_js) ? filemtime($edit_js) : false,
            true
        );

        global $wpdb;
        $tutors = $wpdb->get_results("SELECT id, nombre FROM {$wpdb->prefix}tutores", ARRAY_A);

        wp_localize_script(
            'tb-admin-edit',
            'tbEditData',
            [
                'nonce'  => wp_create_nonce('tb_booking_nonce'),
                'tutors' => $tutors,
            ]
        );
    }
}

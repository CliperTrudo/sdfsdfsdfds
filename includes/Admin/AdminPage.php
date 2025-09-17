<?php
namespace TutoriasBooking\Admin;

require_once TB_PLUGIN_DIR . 'includes/Admin/AdminController.php';

/**
 * Wrapper function to display the tutors admin page.
 */
function tb_pagina_tutores() {
    AdminController::render_tutores_page();
}

/**
 * Wrapper function to display the reserve students admin page.
 */
function tb_pagina_alumnos() {
    AdminController::render_alumnos_page();
}

/**
 * Wrapper function to display the appointments admin page.
 */
function tb_pagina_citas() {
    AdminController::render_citas_page();
}

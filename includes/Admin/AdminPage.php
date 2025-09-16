<?php
namespace TutoriasBooking\Admin;

require_once TB_PLUGIN_DIR . 'includes/Admin/AdminController.php';

/**
 * Wrapper function to display the admin page.
 */
function tb_pagina_tutores() {
    AdminController::handle_tutores_page();
}

function tb_pagina_alumnos() {
    AdminController::handle_alumnos_page();
}

function tb_pagina_citas() {
    AdminController::handle_citas_page();
}

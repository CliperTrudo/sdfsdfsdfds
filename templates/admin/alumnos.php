<?php
/**
 * @var array<int, array{type:string,text:string}> $messages
 * @var bool $table_exists
 * @var string $current_search
 * @var \TutoriasBooking\Admin\AlumnosListTable|null $alumnos_table
 */
?>
<div class="tb-admin-wrapper">
    <?php foreach ($messages as $msg): ?>
        <div class="<?php echo $msg['type'] === 'success' ? 'notice-success' : 'notice-error'; ?> notice is-dismissible tb-notice">
            <p><?php echo esc_html($msg['text']); ?></p>
        </div>
    <?php endforeach; ?>

    <div class="tb-card">
        <h2 class="tb-subtitle"><?php esc_html_e('Gestión de Alumnos de Reserva', 'tutorias-booking'); ?></h2>
        <?php if ($table_exists): ?>
            <form method="POST" class="tb-form">
                <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
                <input type="text" name="tb_alumno_dni" placeholder="<?php esc_attr_e('DNI del Alumno', 'tutorias-booking'); ?>" required>
                <input type="text" name="tb_alumno_nombre" placeholder="<?php esc_attr_e('Nombre', 'tutorias-booking'); ?>" required>
                <input type="text" name="tb_alumno_apellido" placeholder="<?php esc_attr_e('Apellido', 'tutorias-booking'); ?>" required>
                <input type="email" name="tb_alumno_email" placeholder="<?php esc_attr_e('Email del Alumno', 'tutorias-booking'); ?>" required>
                <label><input type="checkbox" name="tb_alumno_online" value="1"> <?php esc_html_e('Online', 'tutorias-booking'); ?></label>
                <label><input type="checkbox" name="tb_alumno_presencial" value="1"> <?php esc_html_e('Presencial', 'tutorias-booking'); ?></label>
                <button type="submit" name="tb_add_alumno_reserva" class="tb-button"><?php esc_html_e('Añadir Alumno', 'tutorias-booking'); ?></button>
            </form>
            <form method="POST" enctype="multipart/form-data" class="tb-form">
                <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
                <input type="file" name="tb_alumnos_file" accept=".xlsx" required>
                <button type="submit" name="tb_import_alumnos" class="tb-button"><?php esc_html_e('Importar Alumnos', 'tutorias-booking'); ?></button>
            </form>
        <?php else: ?>
            <p><em><?php esc_html_e('El formulario no está disponible. Activa el plugin nuevamente.', 'tutorias-booking'); ?></em></p>
        <?php endif; ?>
    </div>

    <?php if ($table_exists && $alumnos_table instanceof \TutoriasBooking\Admin\AlumnosListTable): ?>
        <div class="tb-card">
            <h2 class="tb-subtitle"><?php esc_html_e('Alumnos en la Tabla de Reserva', 'tutorias-booking'); ?></h2>
            <form method="get" class="tb-form tb-form-inline">
                <input type="hidden" name="page" value="tb-alumnos">
                <?php $alumnos_table->search_box(__('Buscar alumnos', 'tutorias-booking'), 'tb-buscar-alumnos'); ?>
                <?php if ($current_search !== ''): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=tb-alumnos')); ?>" class="tb-button"><?php esc_html_e('Limpiar', 'tutorias-booking'); ?></a>
                <?php endif; ?>
            </form>
            <form method="post">
                <input type="hidden" name="page" value="tb-alumnos">
                <?php wp_nonce_field('bulk-alumnos'); ?>
                <?php $alumnos_table->display(); ?>
            </form>
            <form method="POST" onsubmit="return confirm('<?php echo esc_js(__('¿Eliminar todos los alumnos?', 'tutorias-booking')); ?>');" class="tb-form">
                <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
                <button type="submit" name="tb_delete_all_alumnos" class="tb-button tb-button-danger"><?php esc_html_e('Eliminar Todos los Alumnos', 'tutorias-booking'); ?></button>
            </form>
        </div>
    <?php endif; ?>
</div>

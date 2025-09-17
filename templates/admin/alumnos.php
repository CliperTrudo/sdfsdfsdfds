<div class="tb-admin-wrapper">
    <?php foreach ($messages as $msg): ?>
        <div class="<?php echo $msg['type'] === 'success' ? 'notice-success' : 'notice-error'; ?> notice is-dismissible tb-notice">
            <p><?php echo esc_html($msg['text']); ?></p>
        </div>
    <?php endforeach; ?>

    <h1 class="tb-title">Gestión de Alumnos de Reserva</h1>

    <section class="tb-section">
        <h2 class="tb-subtitle">Añadir Alumno a Reserva</h2>

        <?php if ($table_exists): ?>
            <form method="POST" class="tb-form">
                <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
                <div class="tb-form-field">
                    <label for="tb_alumno_dni">DNI del alumno</label>
                    <input type="text" id="tb_alumno_dni" name="tb_alumno_dni" placeholder="DNI del Alumno" required>
                </div>
                <div class="tb-form-field">
                    <label for="tb_alumno_nombre">Nombre</label>
                    <input type="text" id="tb_alumno_nombre" name="tb_alumno_nombre" placeholder="Nombre" required>
                </div>
                <div class="tb-form-field">
                    <label for="tb_alumno_apellido">Apellido</label>
                    <input type="text" id="tb_alumno_apellido" name="tb_alumno_apellido" placeholder="Apellido" required>
                </div>
                <div class="tb-form-field">
                    <label for="tb_alumno_email">Email del alumno</label>
                    <input type="email" id="tb_alumno_email" name="tb_alumno_email" placeholder="Email del Alumno" required>
                </div>
                <div class="tb-form-field tb-form-field--checkbox">
                    <input type="checkbox" id="tb_alumno_online" name="tb_alumno_online" value="1">
                    <label for="tb_alumno_online">Online</label>
                </div>
                <div class="tb-form-field tb-form-field--checkbox">
                    <input type="checkbox" id="tb_alumno_presencial" name="tb_alumno_presencial" value="1">
                    <label for="tb_alumno_presencial">Presencial</label>
                </div>
                <button type="submit" name="tb_add_alumno_reserva" class="tb-button">Añadir Alumno</button>
            </form>

            <form method="POST" enctype="multipart/form-data" class="tb-form">
                <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
                <div class="tb-form-field">
                    <label for="tb_alumnos_file">Archivo de alumnos (.xlsx)</label>
                    <input type="file" id="tb_alumnos_file" name="tb_alumnos_file" accept=".xlsx" required>
                </div>
                <button type="submit" name="tb_import_alumnos" class="tb-button">Importar Alumnos</button>
            </form>
        <?php else: ?>
            <p><em>El formulario no está disponible. Activa el plugin nuevamente.</em></p>
        <?php endif; ?>
    </section>

    <section class="tb-section">
        <h2 class="tb-subtitle">Alumnos en la Tabla de Reserva</h2>

        <?php if ($table_exists): ?>
            <?php if (isset($alumnos_table)): ?>
                <form method="post">
                    <input type="hidden" name="page" value="<?php echo esc_attr('tb-alumnos'); ?>">
                    <?php $alumnos_table->search_box(__('Buscar alumnos', 'tutorias-booking'), 'tb-alumnos-search'); ?>
                    <?php $alumnos_table->display(); ?>
                </form>

                <form method="POST" onsubmit="return confirm('¿Eliminar todos los alumnos?');" class="tb-form">
                    <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
                    <button type="submit" name="tb_delete_all_alumnos" class="tb-button tb-button-danger">Eliminar Todos los Alumnos</button>
                </form>
            <?php else: ?>
                <p><em>No se pudo cargar la tabla de alumnos.</em></p>
            <?php endif; ?>
        <?php else: ?>
            <p><em>La tabla de alumnos no está disponible. Activa el plugin nuevamente.</em></p>
        <?php endif; ?>
    </section>
</div>

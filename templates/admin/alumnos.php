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
            <form method="GET" class="tb-form">
                <input type="hidden" name="page" value="tb-alumnos">
                <div class="tb-form-field">
                    <label for="tb_search_student">Buscar alumno (DNI o nombre)</label>
                    <input type="text" id="tb_search_student" name="tb_search_student" placeholder="Buscar por DNI o Nombre" value="<?php echo esc_attr($search_student); ?>">
                </div>
                <button type="submit" class="tb-button">Buscar</button>
                <?php if (!empty($search_student)): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=tb-alumnos')); ?>" class="tb-button">Limpiar</a>
                <?php endif; ?>
            </form>

            <?php if (!empty($alumnos_reserva)): ?>
                <table class="tb-table">
                    <thead>
                        <tr><th>ID</th><th>DNI</th><th>Nombre</th><th>Apellido</th><th>Email</th><th>Online</th><th>Presencial</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alumnos_reserva as $alumno): ?>
                            <?php $alumno_id = isset($alumno['id']) ? absint($alumno['id']) : 0; ?>
                            <tr>
                                <td><?php echo esc_html($alumno_id); ?></td>
                                <td><?php echo esc_html($alumno['dni']); ?></td>
                                <td><?php echo esc_html($alumno['nombre']); ?></td>
                                <td><?php echo esc_html($alumno['apellido']); ?></td>
                                <td><?php echo esc_html($alumno['email']); ?></td>
                                <td>
                                    <label class="screen-reader-text" for="tb_online_<?php echo esc_attr($alumno_id); ?>">
                                        <?php echo esc_html(sprintf('Estado online para %s %s', $alumno['nombre'], $alumno['apellido'])); ?>
                                    </label>
                                    <input type="checkbox" id="tb_online_<?php echo esc_attr($alumno_id); ?>" name="tb_online" value="1" form="tb_update_<?php echo esc_attr($alumno_id); ?>" <?php checked($alumno['online']); ?>>
                                </td>
                                <td>
                                    <label class="screen-reader-text" for="tb_presencial_<?php echo esc_attr($alumno_id); ?>">
                                        <?php echo esc_html(sprintf('Estado presencial para %s %s', $alumno['nombre'], $alumno['apellido'])); ?>
                                    </label>
                                    <input type="checkbox" id="tb_presencial_<?php echo esc_attr($alumno_id); ?>" name="tb_presencial" value="1" form="tb_update_<?php echo esc_attr($alumno_id); ?>" <?php checked($alumno['presencial']); ?>>
                                </td>
                                <td>
                                    <form method="POST" id="tb_update_<?php echo esc_attr($alumno_id); ?>" class="tb-inline-form">
                                        <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
                                        <input type="hidden" name="tb_update_alumno_id" value="<?php echo esc_attr($alumno_id); ?>">
                                        <button type="submit" class="tb-button">Actualizar</button>
                                    </form>
                                    <form method="POST" class="tb-inline-form" onsubmit="return confirm('¿Eliminar este alumno?');">
                                        <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
                                        <input type="hidden" name="tb_delete_alumno_id" value="<?php echo esc_attr($alumno_id); ?>">
                                        <button type="submit" class="tb-button tb-button-danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (empty($search_student) && $total_pages > 1): ?>
                    <div class="tb-pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a class="tb-button <?php echo ($i === $current_page) ? 'active' : ''; ?>" href="<?php echo esc_url('admin.php?page=tb-alumnos&tb_page=' . $i); ?>"><?php echo esc_html($i); ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" onsubmit="return confirm('¿Eliminar todos los alumnos?');" class="tb-form">
                    <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
                    <button type="submit" name="tb_delete_all_alumnos" class="tb-button tb-button-danger">Eliminar Todos los Alumnos</button>
                </form>
            <?php else: ?>
                <p><em>No hay alumnos registrados.</em></p>
            <?php endif; ?>
        <?php else: ?>
            <p><em>La tabla de alumnos no está disponible. Activa el plugin nuevamente.</em></p>
        <?php endif; ?>
    </section>
</div>

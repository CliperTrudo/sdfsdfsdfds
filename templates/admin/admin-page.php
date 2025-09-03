<?php global $wpdb; ?>
<div class="tb-admin-wrapper">

    <?php foreach ($messages as $msg): ?>
        <div class="<?php echo $msg['type'] === 'success' ? 'notice-success' : 'notice-error'; ?> notice is-dismissible tb-notice">
            <p><?php echo esc_html($msg['text']); ?></p>
        </div>
    <?php endforeach; ?>

    <h1 class="tb-title">Gestión de Tutores</h1>

    <section class="tb-section">
        <h2 class="tb-subtitle">Tutores Registrados</h2>

        <form method="POST" class="tb-form">
            <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
            <input name="tb_nombre" placeholder="Nombre" required>
            <input name="tb_email" type="email" placeholder="Email (Calendar ID)" required>
            <button type="submit" class="tb-button">Agregar Tutor</button>
        </form>

        <form method="POST" enctype="multipart/form-data" class="tb-form">
            <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
            <input type="file" name="tb_tutores_file" accept=".xlsx" required>
            <button type="submit" name="tb_import_tutores" class="tb-button">Importar Tutores</button>
        </form>

        <table class="tb-table">
            <thead>
                <tr><th>Nombre</th><th>Email</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($tutores as $t): ?>
                    <?php $tok = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutores_tokens WHERE tutor_id=%d", $t->id)); ?>
                    <?php $est = $tok ? '✅ Conectado' : '❌ No conectado'; ?>
                    <?php $url = admin_url("admin.php?page=tb-tutores&action=tb_auth_google&tutor_id={$t->id}"); ?>
                    <?php $avail_url = admin_url("admin.php?page=tb-tutores&action=tb_assign_availability&tutor_id={$t->id}"); ?>
                    <tr>
                        <td><?php echo esc_html($t->nombre); ?></td>
                        <td><?php echo esc_html($t->email); ?></td>
                        <td><?php echo $est; ?></td>
                        <td>
                            <a href="<?php echo esc_url($url); ?>" class="tb-link">Conectar Calendar</a>
                            <span> | </span>
                            <a href="<?php echo esc_url($avail_url); ?>" class="tb-link">Asignar Disponibilidad</a>
                            <form method="POST" class="tb-inline-form" onsubmit="return confirm('¿Eliminar este tutor?');">
                                <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
                                <input type="hidden" name="tb_delete_tutor_id" value="<?php echo esc_attr($t->id); ?>">
                                <button type="submit" class="tb-button tb-button-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <form method="POST" onsubmit="return confirm('¿Eliminar todos los tutores?');" class="tb-form">
            <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
            <button type="submit" name="tb_delete_all_tutores" class="tb-button tb-button-danger">Eliminar Todos los Tutores</button>
        </form>
    </section>

    <hr>

    <h1 class="tb-title">Gestión de Alumnos de Reserva</h1>

    <section class="tb-section">
        <h2 class="tb-subtitle">Añadir Alumno a Reserva</h2>

        <?php if ($table_exists): ?>
            <form method="POST" class="tb-form">
                <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
                <input type="text" name="tb_alumno_dni" placeholder="DNI del Alumno" required>
                <input type="text" name="tb_alumno_nombre" placeholder="Nombre" required>
                <input type="text" name="tb_alumno_apellido" placeholder="Apellido" required>
                <input type="email" name="tb_alumno_email" placeholder="Email del Alumno" required>
                <button type="submit" name="tb_add_alumno_reserva" class="tb-button">Añadir Alumno</button>
            </form>

            <form method="POST" enctype="multipart/form-data" class="tb-form">
                <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
                <input type="file" name="tb_alumnos_file" accept=".xlsx" required>
                <button type="submit" name="tb_import_alumnos" class="tb-button">Importar Alumnos</button>
            </form>
        <?php else: ?>
            <p><em>El formulario no está disponible. Activa el plugin nuevamente.</em></p>
        <?php endif; ?>
    </section>

    <section class="tb-section">
        <h2 class="tb-subtitle">Alumnos en la Tabla de Reserva</h2>

        <?php if ($table_exists): ?>
            <?php if (!empty($alumnos_reserva)): ?>
                <table class="tb-table">
                    <thead>
                        <tr><th>ID</th><th>DNI</th><th>Nombre</th><th>Apellido</th><th>Email</th><th>Tiene Cita</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alumnos_reserva as $alumno): ?>
                            <tr>
                                <td><?php echo esc_html($alumno->id); ?></td>
                                <td><?php echo esc_html($alumno->dni); ?></td>
                                <td><?php echo esc_html($alumno->nombre); ?></td>
                                <td><?php echo esc_html($alumno->apellido); ?></td>
                                <td><?php echo esc_html($alumno->email); ?></td>
                                <td><?php echo $alumno->tiene_cita ? 'Sí' : '<span class="tb-alert">No</span>'; ?></td>
                                <td>
                                    <?php if ($alumno->tiene_cita): ?>
                                        <form method="POST" class="tb-inline-form">
                                            <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
                                            <input type="hidden" name="tb_reset_cita_id" value="<?php echo esc_attr($alumno->id); ?>">
                                            <button type="submit" class="tb-button">Poner en 0</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" class="tb-inline-form" onsubmit="return confirm('¿Eliminar este alumno?');">
                                        <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
                                        <input type="hidden" name="tb_delete_alumno_id" value="<?php echo esc_attr($alumno->id); ?>">
                                        <button type="submit" class="tb-button tb-button-danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

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

    <hr>

    <h1 class="tb-title">Gestión de Citas</h1>

    <section class="tb-section">
        <form id="tb-events-form" class="tb-form">
            <select id="tb_events_tutor">
                <option value="">Todos los tutores</option>
                <?php foreach ($tutores as $t): ?>
                    <option value="<?php echo esc_attr($t->id); ?>"><?php echo esc_html($t->nombre); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="tb_events_user">
                <option value="">Todos los alumnos</option>
                <?php foreach ($alumnos_reserva as $alumno): ?>
                    <option value="<?php echo esc_attr($alumno->id); ?>">
                        <?php echo esc_html($alumno->nombre . ' ' . $alumno->apellido); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="date" id="tb_events_start" placeholder="Fecha inicio (opcional)">
            <input type="date" id="tb_events_end" placeholder="Fecha fin (opcional)">
            <button type="submit" class="tb-button">Ver Citas</button>
        </form>

        <table id="tb-events-table" class="tb-table">
            <thead>
                <tr><th>Título</th><th>Inicio</th><th>Fin</th><th>Acciones</th></tr>
            </thead>
            <tbody></tbody>
        </table>
    </section>
</div>

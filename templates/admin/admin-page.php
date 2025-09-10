<?php global $wpdb; ?>
<div class="tb-admin-wrapper">

    <?php foreach ($messages as $msg): ?>
        <div class="<?php echo $msg['type'] === 'success' ? 'notice-success' : 'notice-error'; ?> notice is-dismissible tb-notice">
            <p><?php echo esc_html($msg['text']); ?></p>
        </div>
    <?php endforeach; ?>

    <details class="tb-dropdown" open>
        <summary class="tb-title">Gestión de Tutores</summary>

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
    </details>

    <details class="tb-dropdown">
        <summary class="tb-title">Gestión de Alumnos de Reserva</summary>

    <section class="tb-section">
        <h2 class="tb-subtitle">Añadir Alumno a Reserva</h2>

        <?php if ($table_exists): ?>
            <form method="POST" class="tb-form">
                <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
                <input type="text" name="tb_alumno_dni" placeholder="DNI del Alumno" required>
                <input type="text" name="tb_alumno_nombre" placeholder="Nombre" required>
                <input type="text" name="tb_alumno_apellido" placeholder="Apellido" required>
                <input type="email" name="tb_alumno_email" placeholder="Email del Alumno" required>
                <label><input type="checkbox" name="tb_alumno_online" value="1"> Online</label>
                <label><input type="checkbox" name="tb_alumno_presencial" value="1"> Presencial</label>
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
                        <tr><th>ID</th><th>DNI</th><th>Nombre</th><th>Apellido</th><th>Email</th><th>Online</th><th>Presencial</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alumnos_reserva as $alumno): ?>
                            <tr>
                                <td><?php echo esc_html($alumno->id); ?></td>
                                <td><?php echo esc_html($alumno->dni); ?></td>
                                <td><?php echo esc_html($alumno->nombre); ?></td>
                                <td><?php echo esc_html($alumno->apellido); ?></td>
                                <td><?php echo esc_html($alumno->email); ?></td>
                                <td><input type="checkbox" name="tb_online" value="1" form="tb_update_<?php echo esc_attr($alumno->id); ?>" <?php checked($alumno->online); ?>></td>
                                <td><input type="checkbox" name="tb_presencial" value="1" form="tb_update_<?php echo esc_attr($alumno->id); ?>" <?php checked($alumno->presencial); ?>></td>
                                <td>
                                    <form method="POST" id="tb_update_<?php echo esc_attr($alumno->id); ?>" class="tb-inline-form">
                                        <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
                                        <input type="hidden" name="tb_update_alumno_id" value="<?php echo esc_attr($alumno->id); ?>">
                                        <button type="submit" class="tb-button">Actualizar</button>
                                    </form>
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
    </details>

    <details class="tb-dropdown">
        <summary class="tb-title">Gestión de Citas</summary>

    <section class="tb-section">
        <form id="tb-events-form" class="tb-form">
            <select id="tb_events_tutor">
                <option value="">Todos los tutores</option>
                <?php foreach ($tutores as $t): ?>
                    <option value="<?php echo esc_attr($t->id); ?>"><?php echo esc_html($t->nombre); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="tb_events_dni" placeholder="DNI del alumno (opcional)">
            <select id="tb_events_modalidad">
                <option value="">Todos</option>
                <option value="online">online</option>
                <option value="presencial">presencial</option>
            </select>
            <input type="date" id="tb_events_start" placeholder="Fecha inicio (opcional)">
            <input type="date" id="tb_events_end" placeholder="Fecha fin (opcional)">
            <button type="submit" class="tb-button">Ver Citas</button>
        </form>

        <table id="tb-events-table" class="tb-table">
            <thead>
                <tr><th>Usuario</th><th>Tutor</th><th>Tramo</th><th>Cita</th><th>Acciones</th></tr>
            </thead>
            <tbody></tbody>
        </table>
    </section>
    </details>
    <div id="tb_edit_modal" class="tb-slots-overlay" style="display:none">
        <div class="tb-slots-content">
            <select id="tb_edit_tutor"></select>
            <div id="tb_calendar"></div>
            <p id="tb_selected_slot"></p>
            <button type="button" id="tb_edit_save" class="tb-button" disabled>Guardar</button>
            <button type="button" id="tb_edit_cancel" class="tb-button tb-button-danger">Cancelar</button>
        </div>
    </div>
    <div id="tb_slots_overlay" class="tb-slots-overlay" style="display:none">
        <div id="tb_slots_container" class="tb-slots-content"></div>
    </div>
</div>

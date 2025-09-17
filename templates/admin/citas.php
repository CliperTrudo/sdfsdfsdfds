<div class="tb-admin-wrapper">
    <?php foreach ($messages as $msg): ?>
        <div class="<?php echo $msg['type'] === 'success' ? 'notice-success' : 'notice-error'; ?> notice is-dismissible tb-notice">
            <p><?php echo esc_html($msg['text']); ?></p>
        </div>
    <?php endforeach; ?>

    <h1 class="tb-title">Gestión de Citas</h1>

    <section class="tb-section">
        <form id="tb-events-form" class="tb-form">
            <select id="tb_events_tutor">
                <option value="">Todos los tutores</option>
                <?php foreach ($tutores as $t): ?>
                    <?php $tutor_option_id = isset($t['id']) ? absint($t['id']) : 0; ?>
                    <option value="<?php echo esc_attr($tutor_option_id); ?>"><?php echo esc_html($t['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="tb_events_student" placeholder="DNI o nombre del alumno (opcional)">
            <select id="tb_events_modalidad">
                <option value="">Todos</option>
                <option value="online">online</option>
                <option value="presencial">presencial</option>
            </select>
            <input type="date" id="tb_events_start" placeholder="Fecha inicio (opcional)">
            <input type="date" id="tb_events_end" placeholder="Fecha fin (opcional)">
            <button type="submit" class="tb-button">Ver Citas</button>
            <button type="button" id="tb_export_events" class="tb-button">Exportar XLSX</button>
        </form>

        <table id="tb-events-table" class="tb-table">
            <thead>
                <tr><th>Usuario</th><th>DNI</th><th>Email</th><th>Tutor</th><th>Tramo</th><th>Modalidad</th><th>Cita</th><th>Acciones</th></tr>
            </thead>
            <tbody></tbody>
        </table>
    </section>

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

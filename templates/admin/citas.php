<?php
/**
 * @var array<int, array{type:string,text:string}> $messages
 * @var array<int, object> $tutores
 */
?>
<div class="tb-admin-wrapper">
    <?php foreach ($messages as $msg): ?>
        <div class="<?php echo $msg['type'] === 'success' ? 'notice-success' : 'notice-error'; ?> notice is-dismissible tb-notice">
            <p><?php echo esc_html($msg['text']); ?></p>
        </div>
    <?php endforeach; ?>

    <div class="tb-card">
        <h2 class="tb-subtitle"><?php esc_html_e('Gestión de Citas', 'tutorias-booking'); ?></h2>
        <form id="tb-events-form" class="tb-form tb-form-inline">
            <label class="screen-reader-text" for="tb_events_tutor"><?php esc_html_e('Filtrar por tutor', 'tutorias-booking'); ?></label>
            <select id="tb_events_tutor">
                <option value=""><?php esc_html_e('Todos los tutores', 'tutorias-booking'); ?></option>
                <?php foreach ($tutores as $tutor): ?>
                    <option value="<?php echo esc_attr($tutor->id ?? ''); ?>"><?php echo esc_html($tutor->nombre ?? ''); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="screen-reader-text" for="tb_events_student"><?php esc_html_e('Buscar por alumno', 'tutorias-booking'); ?></label>
            <input type="text" id="tb_events_student" placeholder="<?php esc_attr_e('DNI o nombre del alumno (opcional)', 'tutorias-booking'); ?>">
            <label class="screen-reader-text" for="tb_events_modalidad"><?php esc_html_e('Filtrar por modalidad', 'tutorias-booking'); ?></label>
            <select id="tb_events_modalidad">
                <option value=""><?php esc_html_e('Todos', 'tutorias-booking'); ?></option>
                <option value="online"><?php esc_html_e('Online', 'tutorias-booking'); ?></option>
                <option value="presencial"><?php esc_html_e('Presencial', 'tutorias-booking'); ?></option>
            </select>
            <label class="screen-reader-text" for="tb_events_start"><?php esc_html_e('Fecha inicio', 'tutorias-booking'); ?></label>
            <input type="date" id="tb_events_start" placeholder="<?php esc_attr_e('Fecha inicio (opcional)', 'tutorias-booking'); ?>">
            <label class="screen-reader-text" for="tb_events_end"><?php esc_html_e('Fecha fin', 'tutorias-booking'); ?></label>
            <input type="date" id="tb_events_end" placeholder="<?php esc_attr_e('Fecha fin (opcional)', 'tutorias-booking'); ?>">
            <button type="submit" class="tb-button"><?php esc_html_e('Ver Citas', 'tutorias-booking'); ?></button>
            <button type="button" id="tb_export_events" class="tb-button"><?php esc_html_e('Exportar XLSX', 'tutorias-booking'); ?></button>
        </form>

        <table id="tb-events-table" class="tb-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Usuario', 'tutorias-booking'); ?></th>
                    <th><?php esc_html_e('DNI', 'tutorias-booking'); ?></th>
                    <th><?php esc_html_e('Email', 'tutorias-booking'); ?></th>
                    <th><?php esc_html_e('Tutor', 'tutorias-booking'); ?></th>
                    <th><?php esc_html_e('Tramo', 'tutorias-booking'); ?></th>
                    <th><?php esc_html_e('Modalidad', 'tutorias-booking'); ?></th>
                    <th><?php esc_html_e('Cita', 'tutorias-booking'); ?></th>
                    <th><?php esc_html_e('Acciones', 'tutorias-booking'); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div id="tb_edit_modal" class="tb-slots-overlay" style="display:none">
        <div class="tb-slots-content">
            <label class="screen-reader-text" for="tb_edit_tutor"><?php esc_html_e('Seleccionar tutor', 'tutorias-booking'); ?></label>
            <select id="tb_edit_tutor"></select>
            <div id="tb_calendar"></div>
            <p id="tb_selected_slot"></p>
            <button type="button" id="tb_edit_save" class="tb-button" disabled><?php esc_html_e('Guardar', 'tutorias-booking'); ?></button>
            <button type="button" id="tb_edit_cancel" class="tb-button tb-button-danger"><?php esc_html_e('Cancelar', 'tutorias-booking'); ?></button>
        </div>
    </div>
    <div id="tb_slots_overlay" class="tb-slots-overlay" style="display:none">
        <div id="tb_slots_container" class="tb-slots-content"></div>
    </div>
</div>

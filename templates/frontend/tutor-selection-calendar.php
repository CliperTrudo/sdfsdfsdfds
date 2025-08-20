<div id="tb_step_tutor" class="tb-step" style="display:none;">
    <h3>Reservar Tutoría</h3>
    <p class="tb-summary">
        <strong>DNI:</strong> <span id="tb_summary_dni"></span> |
        <strong>Email:</strong> <span id="tb_summary_email"></span> |
        <strong>Fecha de Examen:</strong> <span id="tb_summary_exam_date"></span>
    </p>

    <form id="tb_booking_form">
        <input type="hidden" id="tb_dni_final" name="dni">
        <input type="hidden" id="tb_email_final" name="email">
        <input type="hidden" id="tb_exam_date_final" name="exam_date">
        <?php wp_nonce_field('tb_booking_nonce', 'tb_booking_nonce_field'); ?>
        <div class="tb-tutor-selection-row">
            <div class="tb-tutor-select-wrapper">
                <label for="tb_tutor_select">Selecciona un Tutor:</label>
                <select id="tb_tutor_select" name="tutor_id" required>
                    <option value="">-- Selecciona un tutor --</option>
                    <?php foreach ($tutores as $tutor): ?>
                        <option value="<?php echo esc_attr($tutor->id); ?>"><?php echo esc_html($tutor->nombre); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="tb-action-feedback-area">
                <div id="tb_response_message" class="tb-message tb-hidden"></div>
                <input type="submit" id="tb_submit_booking" value="Confirmar Reserva" class="tb-button" disabled>
            </div>
        </div>
        <div id="tb_calendar_container" class="tb-calendar-container">
            <p>Selecciona un tutor para ver las franjas horarias disponibles.</p>
        </div>
    </form>
    <div id="tb_booking_details_container"></div>
</div>

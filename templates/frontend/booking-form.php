<div id="tb_dni_step" class="tb-container" style="<?php echo esc_attr($container_style); ?>">
    <h3>Verificación de DNI</h3>
    <form id="tb_dni_form" method="post">
        <?php wp_nonce_field('tb_booking_nonce', 'tb_booking_nonce_field'); ?>
        <p class="tb-form-group">
            <label for="tb_dni">Introduce tu DNI:</label>
            <input type="text" id="tb_dni" name="tb_dni" required placeholder="Ej: 12345678A">
        </p>
        <p class="tb-form-group">
            <label for="tb_email">Introduce tu correo electrónico:</label>
            <input type="email" id="tb_email" name="tb_email" required placeholder="ejemplo@correo.com">
        </p>
        <?php if (TB_RECAPTCHA_SITE_KEY) :
            $recaptcha_theme = getenv('TB_RECAPTCHA_THEME');
        ?>
        <div class="g-recaptcha" data-sitekey="<?php echo esc_attr(TB_RECAPTCHA_SITE_KEY); ?>"<?php echo $recaptcha_theme ? ' data-theme="' . esc_attr($recaptcha_theme) . '"' : ''; ?>></div>
        <?php endif; ?>
        <p class="tb-form-actions">
            <input type="submit" name="tb_submit_dni" value="Verificar Datos" class="tb-button">
        </p>
    </form>
    <div id="tb_dni_message" class="tb-message tb-hidden"></div>
</div>

<div id="tb_exam_date_step" class="tb-container tb-hidden" style="display:none;<?php echo esc_attr($container_style); ?>">
    <button type="button" id="tb_back_to_dni" class="tb-back-button">&larr;</button>
    <h3>Seleccionar Fecha de Examen</h3>
    <form id="tb_exam_date_form" method="post">
        <input type="hidden" id="tb_dni_verified" name="tb_dni_verified" value="<?php echo esc_attr($dni_verified); ?>">
        <input type="hidden" id="tb_email_verified" name="tb_email_verified" value="<?php echo esc_attr($email_verified); ?>">
        <p class="tb-form-group">
            <label for="tb_exam_date">Fecha del Examen:</label>
            <input type="date" id="tb_exam_date" name="tb_exam_date" required
                   min="<?php echo esc_attr($current_date); ?>"
                   value="<?php echo esc_attr($exam_date_selected); ?>">
        </p>
        <p class="tb-form-actions">
            <input type="submit" name="tb_submit_exam_date" value="Siguiente" class="tb-button">
        </p>
    </form>
    <div id="tb_exam_date_message" class="tb-message tb-hidden"></div>
</div>

<div id="tb_tutor_selection_step" class="tb-container tb-hidden" style="display:none;<?php echo esc_attr($container_style); ?>">
    <button type="button" id="tb_back_to_exam_date" class="tb-back-button">&larr;</button>
    <h3>Reservar Entrevista</h3>
    <p id="tb_summary" class="tb-summary">
        <strong>Fecha de Examen:</strong> <?php echo esc_html($exam_date_selected); ?>
    </p>

    <form id="tb_booking_form">
        <input type="hidden" id="tb_dni_final" name="dni" value="<?php echo esc_attr($dni_verified); ?>">
        <input type="hidden" id="tb_email_final" name="email" value="<?php echo esc_attr($email_verified); ?>">
        <input type="hidden" id="tb_exam_date_final" name="exam_date"
            value="<?php echo esc_attr($exam_date_selected); ?>">
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
        </div>
        <div id="tb_calendar_container" class="tb-calendar-container">
            <p>Selecciona un tutor para ver las franjas horarias disponibles.</p>
        </div>
        <div id="tb_confirmation_area" class="tb-confirm-area">
            <span id="tb_selected_slot" class="tb-selected-slot"></span>
            <input type="submit" id="tb_submit_booking" value="Confirmar Reserva" class="tb-button" disabled>
        </div>
        <div id="tb_response_message" class="tb-message tb-hidden"></div>
    </form>
    <div id="tb_booking_details_container"></div>
</div>

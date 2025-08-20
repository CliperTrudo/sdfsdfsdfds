<div id="tb_step_exam" class="tb-step" style="display:none;">
    <h3>Seleccionar Fecha de Examen</h3>
    <div id="tb_exam_response"></div>
    <form id="tb_exam_date_form">
        <p class="tb-form-group">
            <label for="tb_exam_date">Fecha del Examen:</label>
            <input type="date" id="tb_exam_date" name="tb_exam_date" required
                   min="<?php echo esc_attr($current_date); ?>">
        </p>
        <p class="tb-form-actions">
            <input type="submit" name="tb_submit_exam_date" value="Siguiente" class="tb-button">
        </p>
    </form>
</div>

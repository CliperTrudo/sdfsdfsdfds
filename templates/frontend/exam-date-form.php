        <div id="tb_exam_date_step" class="tb-container tb-hidden" <?php echo $hidden_style; ?>>
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
        </div>
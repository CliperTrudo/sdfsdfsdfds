<div class="tb-container" <?php echo $container_style; ?>>
    <h3>Conectar Tutor</h3>
    <?php if (!empty($message)): ?>
        <p class="tb-message tb-message-<?php echo esc_attr($message_type); ?>"><?php echo esc_html($message); ?></p>
    <?php endif; ?>

    <?php if ($auth_url): ?>
        <p class="tb-form-actions">
            <a class="tb-button" href="<?php echo esc_url($auth_url); ?>">Conectar con Google Calendar</a>
        </p>
    <?php else: ?>
        <form method="post">
            <?php wp_nonce_field('tb_tutor_connect', 'tb_tutor_connect_nonce'); ?>
            <p class="tb-form-group">
                <label for="tb_tutor_email">Correo electrónico:</label>
                <input type="email" id="tb_tutor_email" name="tb_tutor_email" required>
            </p>
            <p class="tb-form-actions">
                <input type="submit" class="tb-button" value="Verificar correo">
            </p>
        </form>
    <?php endif; ?>
</div>

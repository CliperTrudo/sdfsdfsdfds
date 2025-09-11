<div class="tb-container" style="<?php echo esc_attr($container_style); ?>">
    <h3>Conectar Tutor</h3>
    <?php if (!empty($message)): ?>
        <p class="tb-message tb-message-<?php echo esc_attr($message_type); ?>"><?php echo esc_html($message); ?></p>
    <?php endif; ?>

    <p class="tb-form-actions">
        <a class="tb-button" href="<?php echo esc_url($auth_url); ?>">Conectar con Google Calendar</a>
    </p>
</div>

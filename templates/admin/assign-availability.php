<?php /** @var array $messages */ ?>
<div class="tb-admin-wrapper">
    <?php foreach ($messages as $msg): ?>
        <div class="<?php echo $msg['type'] === 'success' ? 'notice-success' : 'notice-error'; ?> notice is-dismissible tb-notice">
            <p><?php echo esc_html($msg['text']); ?></p>
        </div>
    <?php endforeach; ?>

    <div class="tb-card">
        <h2>Asignar Disponibilidad a <?php echo esc_html($tutor->nombre); ?></h2>
        <form method="POST">
            <label for="tb-start">Inicio</label>
            <input id="tb-start" type="time" name="tb_start_time" required>
            <label for="tb-end">Fin</label>
            <input id="tb-end" type="time" name="tb_end_time" required>
            <div id="tb-calendar"></div>
            <ul id="tb-selected-dates"></ul>
            <div id="tb-hidden-dates"></div>
            <button type="submit" name="tb_assign_availability" class="tb-button">Guardar</button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=tb-tutores')); ?>" class="tb-button">Volver</a>
        </form>
    </div>
</div>

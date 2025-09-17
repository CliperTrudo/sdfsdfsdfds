<?php
/**
 * @var array<int, array{type:string,text:string}> $messages
 * @var \TutoriasBooking\Admin\TutorsListTable $tutors_table
 */
?>
<div class="tb-admin-wrapper">
    <?php foreach ($messages as $msg): ?>
        <div class="<?php echo $msg['type'] === 'success' ? 'notice-success' : 'notice-error'; ?> notice is-dismissible tb-notice">
            <p><?php echo esc_html($msg['text']); ?></p>
        </div>
    <?php endforeach; ?>

    <div class="tb-card">
        <h2 class="tb-subtitle"><?php esc_html_e('Gestión de Tutores', 'tutorias-booking'); ?></h2>
        <form method="POST" class="tb-form">
            <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
            <input name="tb_nombre" placeholder="<?php esc_attr_e('Nombre', 'tutorias-booking'); ?>" required>
            <input name="tb_email" type="email" placeholder="<?php esc_attr_e('Email (Calendar ID)', 'tutorias-booking'); ?>" required>
            <button type="submit" class="tb-button"><?php esc_html_e('Agregar Tutor', 'tutorias-booking'); ?></button>
        </form>
        <form method="POST" enctype="multipart/form-data" class="tb-form">
            <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
            <input type="file" name="tb_tutores_file" accept=".xlsx" required>
            <button type="submit" name="tb_import_tutores" class="tb-button"><?php esc_html_e('Importar Tutores', 'tutorias-booking'); ?></button>
        </form>
        <form method="POST" onsubmit="return confirm('<?php echo esc_js(__('¿Eliminar todos los tutores?', 'tutorias-booking')); ?>');" class="tb-form">
            <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
            <button type="submit" name="tb_delete_all_tutores" class="tb-button tb-button-danger"><?php esc_html_e('Eliminar Todos los Tutores', 'tutorias-booking'); ?></button>
        </form>
    </div>

    <div class="tb-card">
        <h2 class="tb-subtitle"><?php esc_html_e('Tutores Registrados', 'tutorias-booking'); ?></h2>
        <form method="get" class="tb-form tb-form-inline">
            <input type="hidden" name="page" value="tb-tutores">
            <?php $tutors_table->search_box(__('Buscar Tutores', 'tutorias-booking'), 'tb-buscar-tutores'); ?>
        </form>
        <form method="post">
            <input type="hidden" name="page" value="tb-tutores">
            <?php wp_nonce_field('bulk-tutores'); ?>
            <?php $tutors_table->display(); ?>
        </form>
    </div>
</div>

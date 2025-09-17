<div class="tb-admin-wrapper">
    <?php foreach ($messages as $msg): ?>
        <div class="<?php echo $msg['type'] === 'success' ? 'notice-success' : 'notice-error'; ?> notice is-dismissible tb-notice">
            <p><?php echo esc_html($msg['text']); ?></p>
        </div>
    <?php endforeach; ?>

    <h1 class="tb-title">Gestión de Tutores</h1>

    <section class="tb-section">
        <h2 class="tb-subtitle">Añadir Tutor</h2>
        <form method="POST" class="tb-form">
            <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
            <div class="tb-form-field">
                <label for="tb_tutor_nombre">Nombre del tutor</label>
                <input id="tb_tutor_nombre" name="tb_nombre" placeholder="Nombre" required>
            </div>
            <div class="tb-form-field">
                <label for="tb_tutor_email">Email del tutor (Calendar ID)</label>
                <input id="tb_tutor_email" name="tb_email" type="email" placeholder="Email (Calendar ID)" required>
            </div>
            <button type="submit" name="tb_add_tutor" class="tb-button">Agregar Tutor</button>
        </form>
    </section>

    <section class="tb-section">
        <h2 class="tb-subtitle">Importar Tutores</h2>
        <form method="POST" enctype="multipart/form-data" class="tb-form">
            <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
            <div class="tb-form-field">
                <label for="tb_tutores_file">Archivo de tutores (.xlsx)</label>
                <input type="file" id="tb_tutores_file" name="tb_tutores_file" accept=".xlsx" required>
            </div>
            <button type="submit" name="tb_import_tutores" class="tb-button">Importar Tutores</button>
        </form>
    </section>

    <section class="tb-section">
        <h2 class="tb-subtitle">Tutores Registrados</h2>
        <?php if (isset($tutors_table)) : ?>
            <form method="post">
                <input type="hidden" name="page" value="<?php echo esc_attr('tb-tutores'); ?>">
                <?php $tutors_table->search_box(__('Buscar tutores', 'tutorias-booking'), 'tb-tutores-search'); ?>
                <?php $tutors_table->display(); ?>
            </form>
        <?php else : ?>
            <p><em>No se pudo cargar la tabla de tutores.</em></p>
        <?php endif; ?>
    </section>

    <section class="tb-section">
        <form method="POST" onsubmit="return confirm('¿Eliminar todos los tutores?');" class="tb-form">
            <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
            <button type="submit" name="tb_delete_all_tutores" class="tb-button tb-button-danger">Eliminar Todos los Tutores</button>
        </form>
    </section>
</div>

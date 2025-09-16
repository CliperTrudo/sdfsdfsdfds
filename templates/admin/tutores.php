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
            <button type="submit" class="tb-button">Agregar Tutor</button>
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
        <table class="tb-table">
            <thead>
                <tr><th>Nombre</th><th>Email</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($tutores as $t): ?>
                    <?php
                        $tutor_id    = isset($t['id']) ? absint($t['id']) : 0;
                        $has_token   = $tutor_id > 0 && !empty($tutores_tokens[$tutor_id]);
                        $status_text = $has_token ? '✅ Conectado' : '❌ No conectado';
                        $url = add_query_arg(
                            [
                                'page'     => 'tb-tutores',
                                'action'   => 'tb_auth_google',
                                'tutor_id' => $tutor_id,
                            ],
                            admin_url('admin.php')
                        );
                        $avail_url = add_query_arg(
                            [
                                'page'     => 'tb-tutores',
                                'action'   => 'tb_assign_availability',
                                'tutor_id' => $tutor_id,
                            ],
                            admin_url('admin.php')
                        );
                    ?>
                    <tr>
                        <td><?php echo esc_html($t['nombre']); ?></td>
                        <td><?php echo esc_html($t['email']); ?></td>
                        <td><?php echo esc_html($status_text); ?></td>
                        <td>
                            <a href="<?php echo esc_url($url); ?>" class="tb-link">Conectar Calendar</a>
                            <span> | </span>
                            <a href="<?php echo esc_url($avail_url); ?>" class="tb-link">Asignar Disponibilidad</a>
                            <form method="POST" class="tb-inline-form" onsubmit="return confirm('¿Eliminar este tutor?');">
                                <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
                                <input type="hidden" name="tb_delete_tutor_id" value="<?php echo esc_attr($tutor_id); ?>">
                                <button type="submit" class="tb-button tb-button-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="tb-section">
        <form method="POST" onsubmit="return confirm('¿Eliminar todos los tutores?');" class="tb-form">
            <?php wp_nonce_field('tb_admin_action', 'tb_admin_nonce'); ?>
            <button type="submit" name="tb_delete_all_tutores" class="tb-button tb-button-danger">Eliminar Todos los Tutores</button>
        </form>
    </section>
</div>

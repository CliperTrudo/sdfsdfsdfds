<div class="tb-admin-wrapper">
    <h1 class="tb-title">Listado de Citas</h1>
    <form method="get" class="tb-form">
        <input type="hidden" name="page" value="tb-citas">
        <input type="date" name="from" value="<?php echo esc_attr($filters['from']); ?>">
        <input type="date" name="to" value="<?php echo esc_attr($filters['to']); ?>">
        <input type="text" name="cliente" placeholder="Cliente" value="<?php echo esc_attr($client); ?>">
        <select name="estado">
            <option value="">Todos</option>
            <option value="pendiente" <?php selected($filters['estado'], 'pendiente'); ?>>Pendiente</option>
            <option value="confirmada" <?php selected($filters['estado'], 'confirmada'); ?>>Confirmada</option>
            <option value="cancelada" <?php selected($filters['estado'], 'cancelada'); ?>>Cancelada</option>
        </select>
        <button type="submit" class="tb-button">Filtrar</button>
    </form>
    <?php if ($appointments): ?>
        <table class="tb-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Tutor</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $cita): ?>
                    <tr>
                        <td><?php echo esc_html($cita->start_datetime); ?></td>
                        <td><?php echo esc_html($cita->participant_name); ?></td>
                        <td><?php echo esc_html($cita->tutor_id); ?></td>
                        <td><?php echo esc_html($cita->estado); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p><em>No se encontraron citas.</em></p>
    <?php endif; ?>
    <?php if ($pages > 1): ?>
        <div class="tablenav">
            <?php if ($page > 1): ?>
                <a class="tablenav-pages-prev button" href="<?php echo esc_url(add_query_arg('paged', $page - 1)); ?>">&laquo; Anterior</a>
            <?php endif; ?>
            <span class="pagination-links"><?php echo $page; ?> / <?php echo $pages; ?></span>
            <?php if ($page < $pages): ?>
                <a class="tablenav-pages-next button" href="<?php echo esc_url(add_query_arg('paged', $page + 1)); ?>">Siguiente &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

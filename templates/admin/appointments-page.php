<?php
/** @var Appointments_List_Table $appointments_table */
?>
<div class="tb-admin-wrapper">
    <h1 class="tb-title">Gestión de Citas</h1>
    <input type="text" id="tb-appointments-filter" placeholder="Filtrar citas..." />
    <form method="post">
        <?php $appointments_table->display(); ?>
    </form>
</div>

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

<?php
global $wpdb;
$tutors = $wpdb->get_results( "SELECT id, nombre FROM {$wpdb->prefix}tutores" );
?>
<div id="tb-edit-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div style="background:#fff; padding:20px; max-width:400px; margin:50px auto;">
        <form id="tb-edit-form">
            <input type="hidden" id="tb-edit-id" name="appointment_id" />
            <input type="hidden" id="tb-edit-event" name="event_id" />
            <p>
                <label>Inicio<br/>
                    <input type="datetime-local" id="tb-edit-start" name="new_start" />
                </label>
            </p>
            <p>
                <label>Fin<br/>
                    <input type="datetime-local" id="tb-edit-end" name="new_end" />
                </label>
            </p>
            <p>
                <label>Tutor<br/>
                    <select id="tb-edit-tutor" name="tutor_id">
                        <?php foreach ( $tutors as $t ) : ?>
                            <option value="<?php echo esc_attr( $t->id ); ?>"><?php echo esc_html( $t->nombre ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </p>
            <p>
                <button type="submit" class="button button-primary">Guardar</button>
                <button type="button" class="button" id="tb-edit-cancel">Cancelar</button>
            </p>
        </form>
    </div>
</div>

<script>
jQuery(function($){
    $('.tb-edit-appointment').on('click', function(){
        $('#tb-edit-id').val($(this).data('id'));
        $('#tb-edit-event').val($(this).data('event'));
        $('#tb-edit-start').val($(this).data('start'));
        $('#tb-edit-end').val($(this).data('end'));
        $('#tb-edit-tutor').val($(this).data('tutor'));
        $('#tb-edit-modal').show();
    });
    $('#tb-edit-cancel').on('click', function(){
        $('#tb-edit-modal').hide();
    });
    $('#tb-edit-form').on('submit', function(e){
        e.preventDefault();
        var data = {
            action: 'tb_edit_appointment',
            nonce: tbAdminData.edit_nonce,
            appointment_id: $('#tb-edit-id').val(),
            event_id: $('#tb-edit-event').val(),
            new_start: $('#tb-edit-start').val(),
            new_end: $('#tb-edit-end').val(),
            tutor_id: $('#tb-edit-tutor').val()
        };
        $.post(ajaxurl, data, function(res){
            if(res.success){
                location.reload();
            } else {
                alert(res.data || 'Error al editar la cita');
            }
        });
    });
});
</script>

jQuery(function($){
    $('#tb-events-form').on('submit', function(e){
        e.preventDefault();
        var tutor = $('#tb_events_tutor').val();
        var user  = $('#tb_events_user').val();
        var start = $('#tb_events_start').val();
        var end   = $('#tb_events_end').val();

        if(!tutor && !user && !start && !end){
            alert('Debe indicar al menos un filtro');
            return;
        }

        var data = {
            action: 'tb_list_events',
            nonce: tbEventsData.nonce
        };
        if(tutor) data.tutor_id = tutor;
        if(user)  data.user_id  = user;
        if(start) data.start_date = start;
        if(end)   data.end_date   = end;

        $('#tb-events-table tbody').empty();
        $.post(ajaxurl, data, function(res){
            if(res.success){
                res.data.forEach(function(ev){
                    var row = '<tr data-event-id="'+ev.id+'">';
                    row += '<td><input type="text" class="tb-event-summary" value="'+(ev.summary||'')+'"></td>';
                    row += '<td><input type="datetime-local" class="tb-event-start" value="'+ev.start.replace(' ','T')+'"></td>';
                    row += '<td><input type="datetime-local" class="tb-event-end" value="'+ev.end.replace(' ','T')+'"></td>';
                    row += '<td><button type="button" class="tb-button tb-save-event">Guardar</button>';
                    row += ' <button type="button" class="tb-button tb-button-danger tb-delete-event">Eliminar</button></td>';
                    row += '</tr>';
                    $('#tb-events-table tbody').append(row);
                });
            } else {
                alert(res.data || 'Error al obtener eventos');
            }
        });
    });

    $('#tb-events-table').on('click', '.tb-save-event', function(){
        var row = $(this).closest('tr');
        $.post(ajaxurl, {
            action: 'tb_update_event',
            tutor_id: $('#tb_events_tutor').val(),
            event_id: row.data('event-id'),
            summary: row.find('.tb-event-summary').val(),
            start: row.find('.tb-event-start').val(),
            end: row.find('.tb-event-end').val(),
            nonce: tbEventsData.nonce
        }, function(res){
            if(!res.success){
                alert(res.data || 'Error al guardar');
            }
        });
    });

    $('#tb-events-table').on('click', '.tb-delete-event', function(){
        if(!confirm('¿Eliminar esta cita?')) return;
        var row = $(this).closest('tr');
        $.post(ajaxurl, {
            action: 'tb_delete_event',
            tutor_id: $('#tb_events_tutor').val(),
            event_id: row.data('event-id'),
            nonce: tbEventsData.nonce
        }, function(res){
            if(res.success){
                row.remove();
            } else {
                alert(res.data || 'Error al eliminar');
            }
        });
    });
});

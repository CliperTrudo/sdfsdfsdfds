jQuery(function($){
    $('#tb-events-form').on('submit', function(e){
        e.preventDefault();
        var tutor     = $('#tb_events_tutor').val();
        var dni       = $('#tb_events_dni').val();
        var modalidad = $('#tb_events_modalidad').val();
        var start     = $('#tb_events_start').val();
        var end       = $('#tb_events_end').val();

        if(!tutor && !dni && !start && !end && !modalidad){
            alert('Debe indicar al menos un filtro');
            return;
        }

        var data = {
            action: 'tb_list_events',
            nonce: tbEventsData.nonce
        };
        if(tutor)     data.tutor_id  = tutor;
        if(dni)       data.dni       = dni;
        if(modalidad) data.modalidad = modalidad;
        if(start)     data.start_date = start;
        if(end)       data.end_date   = end;

        $('#tb-events-table tbody').empty();
        $.post(ajaxurl, data, function(res){
            if(res.success){
                res.data.forEach(function(ev){
                    var row = '<tr data-event-id="'+ev.id+'" data-tutor-id="'+ev.tutor_id+'">';
                    row += '<td>'+(ev.user||'')+'</td>';
                    row += '<td>'+(ev.tutor||'')+'</td>';
                    row += '<td>'+ev.start+' - '+ev.end+'</td>';
                    row += '<td>'+(ev.modalidad||'')+'</td>';
                    var link = ev.url ? '<a href="'+ev.url+'" target="_blank">'+ev.url+'</a>' : '';
                    row += '<td>'+link+'</td>';
                    row += '<td><button type="button" class="tb-button tb-edit-event">Editar</button> ';
                    row += '<button type="button" class="tb-button tb-button-danger tb-delete-event">Eliminar</button></td>';
                    row += '</tr>';
                    $('#tb-events-table tbody').append(row);
                });
            } else {
                alert(res.data || 'Error al obtener eventos');
            }
        });
    });

    $('#tb-events-table').on('click', '.tb-delete-event', function(){
        if(!confirm('¿Eliminar esta cita?')) return;
        var row = $(this).closest('tr');
        $.post(ajaxurl, {
            action: 'tb_delete_event',
            tutor_id: row.data('tutor-id'),
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
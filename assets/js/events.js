
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
                    var $row = $('<tr>')
                        .attr('data-event-id', ev.id)
                        .attr('data-tutor-id', ev.tutor_id);

                    $row.append($('<td>').text(ev.user || ''));
                    $row.append($('<td>').text(ev.dni || ''));
                    $row.append($('<td>').text(ev.email || ''));
                    $row.append($('<td>').text(ev.tutor || ''));
                    $row.append($('<td>').text(ev.start + ' - ' + ev.end));
                    $row.append($('<td>').text(ev.modalidad || ''));

                    var $linkCell = $('<td>');
                    if (ev.url) {
                        var safeUrl = '';
                        try {
                            safeUrl = encodeURI(ev.url);
                        } catch (e) {}

                        if (/^https?:/i.test(safeUrl)) {
                            $linkCell.append(
                                $('<a>')
                                    .attr('href', safeUrl)
                                    .attr('target', '_blank')
                                    .text(safeUrl)
                            );
                        }
                    }
                    $row.append($linkCell);

                    var $actions = $('<td>');
                    $actions.append(
                        $('<button>')
                            .attr('type', 'button')
                            .addClass('tb-button tb-edit-event')
                            .text('Editar')
                    );
                    $actions.append(' ');
                    $actions.append(
                        $('<button>')
                            .attr('type', 'button')
                            .addClass('tb-button tb-button-danger tb-delete-event')
                            .text('Eliminar')
                    );
                    $row.append($actions);

                    $('#tb-events-table tbody').append($row);
                });
            } else {
                alert(res.data || 'Error al obtener eventos');
            }
        });
    });

    $('#tb_export_events').on('click', function(e){
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
            action: 'tb_export_events',
            nonce: tbEventsData.nonce
        };
        if(tutor)     data.tutor_id  = tutor;
        if(dni)       data.dni       = dni;
        if(modalidad) data.modalidad = modalidad;
        if(start)     data.start_date = start;
        if(end)       data.end_date   = end;

        var query = $.param(data);
        // Server will respond with an XLSX file download
        window.open(ajaxurl + '?' + query, '_blank');
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

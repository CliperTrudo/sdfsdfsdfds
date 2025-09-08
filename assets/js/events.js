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
                    var row = '<tr data-event-id="'+ev.id+'" data-tutor-id="'+ev.tutor_id+'" data-start="'+ev.start+'" data-end="'+ev.end+'">';
                    row += '<td>'+(ev.user||'')+'</td>';
                    row += '<td>'+(ev.tutor||'')+'</td>';
                    row += '<td>'+ev.start+' - '+ev.end+'</td>';
                    var link = ev.url ? '<a href="'+ev.url+'" target="_blank">'+ev.url+'</a>' : '';
                    row += '<td>'+link+'</td>';
                    row += '<td><button type="button" class="tb-button tb-edit-event">Editar</button> ' +
                           '<button type="button" class="tb-button tb-button-danger tb-delete-event">Eliminar</button></td>';
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

    $('#tb-events-table').on('click', '.tb-edit-event', function(){
        var row = $(this).closest('tr');
        var eventId = row.data('event-id');
        var tutorId = row.data('tutor-id');
        var currentStart = row.data('start');
        var currentEnd = row.data('end');
        var currentDate = currentStart.split(' ')[0];
        var currentStartTime = currentStart.split(' ')[1];
        var currentEndTime = currentEnd.split(' ')[1];

        var modal = $('#tb-edit-event-modal');
        var calendarContainer = $('#tb-edit-event-calendar');
        calendarContainer.empty();

        var today = new Date();
        var startDate = tbCalendar.formatDate(today);
        var endDateObj = new Date();
        endDateObj.setMonth(endDateObj.getMonth() + 1);
        var endDate = tbCalendar.formatDate(endDateObj);

        $.post(ajaxurl, {
            action: 'tb_get_available_slots',
            tutor_id: tutorId,
            start_date: startDate,
            end_date: endDate,
            nonce: tbEventsData.nonce
        }, function(res){
            if(res.success){
                var slotsByDate = {};
                $.each(res.data, function(i, slot){
                    if(!slotsByDate[slot.date]){ slotsByDate[slot.date] = []; }
                    slotsByDate[slot.date].push(slot);
                });

                var calendarStartDate = new Date(startDate + 'T00:00:00');
                var calendarEndDate = new Date(endDate + 'T00:00:00');
                var currentMonthDate = new Date(currentDate + 'T00:00:00');
                var opts = {
                    calendar: '#tb-edit-event-calendar',
                    slotsByDate: slotsByDate,
                    calendarStartDate: calendarStartDate,
                    calendarEndDate: calendarEndDate,
                    selectedDate: currentDate,
                    overlay: '#tb-edit-event-modal',
                    slotsContainer: '#tb-edit-event-calendar',
                    onSelect: function(slot){
                        if(confirm('Actualizar cita a ' + slot.date + ' ' + slot.start + ' - ' + slot.end + '?')){
                            $.post(ajaxurl, {
                                action: 'tb_update_event',
                                tutor_id: tutorId,
                                event_id: eventId,
                                start: slot.date + ' ' + slot.start,
                                end: slot.date + ' ' + slot.end,
                                nonce: tbEventsData.nonce
                            }, function(r){
                                if(r.success){
                                    modal.hide();
                                    $('#tb-events-form').submit();
                                }else{
                                    alert(r.data || 'Error al actualizar');
                                }
                            });
                        } else {
                            tbCalendar.renderCalendar(currentMonthDate, opts);
                        }
                    }
                };

                tbCalendar.renderCalendar(currentMonthDate, opts);
                tbCalendar.renderSlotsForDate(currentDate, slotsByDate, '#tb-edit-event-modal', '#tb-edit-event-calendar', opts.onSelect);
                $('#tb-edit-event-calendar input[value="'+currentStartTime+'-'+currentEndTime+'"]').prop('checked', true);

                modal.show().css('display','flex');
            } else {
                alert(res.data || 'Error al obtener disponibilidad');
            }
        });

        modal.off('click').on('click', function(e){
            if(e.target.id === 'tb-edit-event-modal') modal.hide();
        });
    });
});

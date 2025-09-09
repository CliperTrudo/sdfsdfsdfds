jQuery(function($){
  var selectedSlot = null;

  $('#tb-events-table').on('click', '.tb-edit-event', function(){
    var row = $(this).closest('tr');
    var tutorId = row.data('tutor-id');
    var eventId = row.data('event-id');

    var today = new Date();
    var startDate = tbCalendarUtils.formatDate(today);
    var endDateObj = new Date(today);
    endDateObj.setFullYear(endDateObj.getFullYear() + 1);
    var endDate = tbCalendarUtils.formatDate(endDateObj);

    $.post(ajaxurl, {
      action: 'tb_get_available_slots',
      tutor_id: tutorId,
      start_date: startDate,
      end_date: endDate,
      nonce: tbEditData.nonce
    }, function(res){
      if(res.success){
        window.slotsByDate = {};
        $.each(res.data, function(i, slot){
          if(!window.slotsByDate[slot.date]) window.slotsByDate[slot.date] = [];
          window.slotsByDate[slot.date].push(slot);
        });
        window.calendarStartDate = new Date(startDate + 'T00:00:00');
        window.calendarEndDate   = new Date(endDate + 'T00:00:00');
        window.currentMonthDate  = new Date(startDate + 'T00:00:00');
        window.selectedDate = null;

        if(!$('#tb_edit_modal').length){
          $('body').append('<div id="tb_edit_modal" class="tb-slots-overlay" style="display:none"><div class="tb-slots-content"><div id="tb_calendar"></div><p id="tb_selected_slot"></p><button type="button" id="tb_edit_save" class="tb-button" disabled>Guardar</button> <button type="button" id="tb_edit_cancel" class="tb-button tb-button-danger">Cancelar</button></div></div>');
        }
        if(!$('#tb_slots_overlay').length){
          $('body').append('<div id="tb_slots_overlay" class="tb-slots-overlay" style="display:none"><div id="tb_slots_container" class="tb-slots-content"></div></div>');
        }

        window.tbCalendarSelector = '#tb_calendar';
        window.tbOnDaySelected = function(date){
          $('#tb_edit_save').prop('disabled', true);
          tbCalendarUtils.renderSlotsForDate(date);
        };
        window.tbOnSlotSelected = function(input){
          selectedSlot = input;
          $('#tb_selected_slot').text('Seleccionado: ' + input.data('date') + ' ' + input.data('start') + ' - ' + input.data('end'));
          $('#tb_edit_save').prop('disabled', false);
        };

        tbCalendarUtils.renderCalendar(window.currentMonthDate);
        $('#tb_edit_modal').show().css('display','flex');

        $('#tb_edit_cancel').off('click').on('click', function(){
          $('#tb_edit_modal').hide();
        });

        $('#tb_edit_save').off('click').on('click', function(){
          if(!selectedSlot) return;
          var start = selectedSlot.data('date') + ' ' + selectedSlot.data('start');
          var end   = selectedSlot.data('date') + ' ' + selectedSlot.data('end');
          $.post(ajaxurl, {
            action: 'tb_update_event',
            tutor_id: tutorId,
            event_id: eventId,
            start: start,
            end: end,
            nonce: tbEventsData.nonce
          }, function(resp){
            if(resp.success){
              row.find('td').eq(2).text(start + ' - ' + end);
              $('#tb_edit_modal').hide();
            } else {
              alert(resp.data || 'Error al actualizar');
            }
          });
        });
      } else {
        alert(res.data || 'Error al obtener disponibilidad');
      }
    });
  });
});

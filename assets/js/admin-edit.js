jQuery(function($){
  var selectedSlot = null;

  $('#tb-events-table').on('click', '.tb-edit-event', function(){
    var row = $(this).closest('tr');
    var originalTutorId = row.data('tutor-id');
    var eventId = row.data('event-id');
    var selectedTutorId = originalTutorId;
    var modalidad = row.find('td').eq(3).text().trim().toLowerCase();

    function populateTutorSelect(){
      if(!$('#tb_edit_modal').length){
        $('body').append('<div id="tb_edit_modal" class="tb-slots-overlay" style="display:none"><div class="tb-slots-content"><select id="tb_edit_tutor"></select><div id="tb_calendar"></div><p id="tb_selected_slot"></p><button type="button" id="tb_edit_save" class="tb-button" disabled>Guardar</button> <button type="button" id="tb_edit_cancel" class="tb-button tb-button-danger">Cancelar</button></div></div>');
      }
      if(!$('#tb_slots_overlay').length){
        $('body').append('<div id="tb_slots_overlay" class="tb-slots-overlay" style="display:none"><div id="tb_slots_container" class="tb-slots-content"></div></div>');
      }
      var sel = $('#tb_edit_tutor');
      sel.empty();
      $.each(tbEditData.tutors, function(i, t){
        sel.append('<option value="'+t.id+'">'+t.nombre+'</option>');
      });
      sel.val(selectedTutorId);
      sel.off('change').on('change', function(){
        selectedTutorId = $(this).val();
        loadSlots(selectedTutorId);
      });
    }

    function loadSlots(tutorId){
      var today = new Date();
      today.setHours(0,0,0,0);

      var startDateObj = new Date(today);
      var endDateObj = new Date(today);
      endDateObj.setDate(endDateObj.getDate() + 60);

      var startDate = tbCalendarUtils.formatDate(startDateObj);
      var endDate   = tbCalendarUtils.formatDate(endDateObj);

      $.post(ajaxurl, {
        action: 'tb_get_available_slots',
        tutor_id: tutorId,
        modalidad: modalidad,
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
          selectedSlot = null;
          $('#tb_selected_slot').text('');
          $('#tb_edit_save').prop('disabled', true);

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
        } else {
          alert(res.data || 'Error al obtener disponibilidad');
        }
      });
    }

    populateTutorSelect();
    loadSlots(selectedTutorId);
    $('#tb_edit_modal').show().css('display','flex');

    $('#tb_edit_cancel').off('click').on('click', function(){
      $('#tb_edit_modal').hide();
      $('#tb_slots_overlay').hide();
    });

    $('#tb_edit_save').off('click').on('click', function(){
      if(!selectedSlot) return;
      var start = selectedSlot.data('date') + ' ' + selectedSlot.data('start');
      var end   = selectedSlot.data('date') + ' ' + selectedSlot.data('end');
      $.post(ajaxurl, {
        action: 'tb_update_event',
        tutor_id: selectedTutorId,
        original_tutor_id: originalTutorId,
        event_id: eventId,
        start: start,
        end: end,
        nonce: tbEventsData.nonce
      }, function(resp){
        if(resp.success){
          row.data('tutor-id', selectedTutorId);
          row.data('event-id', resp.data.event_id);
          row.find('td').eq(1).text($('#tb_edit_tutor option:selected').text());
          row.find('td').eq(2).text(start + ' - ' + end);
          var linkHtml = resp.data.url ? '<a href="'+resp.data.url+'" target="_blank">'+resp.data.url+'</a>' : '';
          row.find('td').eq(4).html(linkHtml);
          $('#tb_edit_modal').hide();
          $('#tb_slots_overlay').hide();
        } else {
          alert(resp.data || 'Error al actualizar');
        }
      });
    });
  });
});

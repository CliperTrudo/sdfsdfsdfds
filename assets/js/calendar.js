(function($){
  function formatDate(date){
    var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();
    if(month.length < 2) month = '0' + month;
    if(day.length < 2) day = '0' + day;
    return [year, month, day].join('-');
  }

  function renderSlotsForDate(date, slotsByDate, overlaySelector, containerSelector, onSelect){
    var overlay = $(overlaySelector);
    var slotsContainer = $(containerSelector);
    slotsContainer.empty();
    var slotsForThisDate = slotsByDate[date];
    if(slotsForThisDate && slotsForThisDate.length > 0){
      var dayHtml = '<button type="button" id="tb_close_slots" class="tb-close-btn">&times;</button>';
      dayHtml += '<div class="tb-day-card">';
      dayHtml += '<h4>' + date + '</h4>';
      dayHtml += '<div class="tb-time-slots-list">';
      $.each(slotsForThisDate, function(idx, slot){
        dayHtml += '<label class="tb-time-slot-label">';
        var val = slot.start_time + '-' + slot.end_time;
        dayHtml += '<input type="radio" name="selected_slot" value="' + val + '" data-start="' + slot.start_time + '" data-end="' + slot.end_time + '" data-date="' + slot.date + '">';
        dayHtml += slot.start_time + ' - ' + slot.end_time;
        dayHtml += '</label>';
      });
      dayHtml += '</div>';
      dayHtml += '</div>';
      slotsContainer.html(dayHtml);
    } else {
      slotsContainer.html('<button type="button" id="tb_close_slots" class="tb-close-btn">&times;</button><p class="tb-message tb-message-info">No hay disponibilidad para la fecha seleccionada.</p>');
    }
    overlay.show().css('display','flex');
    overlay.off('click').on('click', function(e){ if(e.target.id === overlay.attr('id')) overlay.hide(); });
    $('#tb_close_slots').on('click', function(){ overlay.hide(); });
    slotsContainer.off('change', 'input[name="selected_slot"]').on('change','input[name="selected_slot"]', function(){
      if(onSelect){
        onSelect({date: $(this).data('date'), start: $(this).data('start'), end: $(this).data('end')});
      }
    });
  }

  function renderCalendar(monthDate, opts){
    var calendar = $(opts.calendar);
    var slotsByDate = opts.slotsByDate || {};
    var calendarStartDate = opts.calendarStartDate;
    var calendarEndDate = opts.calendarEndDate;
    var selectedDate = opts.selectedDate || null;
    var overlay = opts.overlay;
    var slotsContainer = opts.slotsContainer;
    var onSelect = opts.onSelect;

    var monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    var dayNames = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
    var month = monthDate.getMonth();
    var year = monthDate.getFullYear();
    var calendarHtml = '<div class="tb-calendar-month">';
    calendarHtml += '<div class="tb-calendar-nav">';
    calendarHtml += '<button id="tb_prev_month" class="tb-nav-btn">&lt;</button>';
    calendarHtml += '<span class="tb-calendar-month-name">' + monthNames[month] + ' ' + year + '</span>';
    calendarHtml += '<button id="tb_next_month" class="tb-nav-btn">&gt;</button>';
    calendarHtml += '</div>';
    calendarHtml += '<div class="tb-calendar-week-day-names">';
    $.each(dayNames, function(_, dn){ calendarHtml += '<div class="tb-calendar-week-day">' + dn + '</div>'; });
    calendarHtml += '</div>';
    calendarHtml += '<div class="tb-calendar-days">';
    var firstDayIndex = new Date(year, month, 1).getDay();
    for(var i=0;i<firstDayIndex;i++){ calendarHtml += '<div class="tb-calendar-day tb-empty"></div>'; }
    var daysInMonth = new Date(year, month+1, 0).getDate();
    for(var d=1; d<=daysInMonth; d++){
      var dateObj = new Date(year, month, d);
      var dateStr = formatDate(dateObj);
      if(dateObj < calendarStartDate || dateObj > calendarEndDate){
        calendarHtml += '<div class="tb-calendar-day tb-out-of-range">' + d + '</div>';
      } else {
        var available = !!slotsByDate[dateStr];
        var classes = 'tb-calendar-day' + (available ? ' tb-day-available' : ' tb-day-unavailable');
        if(selectedDate === dateStr) classes += ' tb-selected';
        calendarHtml += '<div class="' + classes + '" data-date="' + dateStr + '">' + d + '</div>';
      }
    }
    calendarHtml += '</div></div>';
    calendar.html(calendarHtml);

    calendar.find('.tb-day-available').on('click', function(){
      selectedDate = $(this).data('date');
      calendar.find('.tb-calendar-day').removeClass('tb-selected');
      $(this).addClass('tb-selected');
      renderSlotsForDate(selectedDate, slotsByDate, overlay, slotsContainer, onSelect);
    });

    calendar.find('#tb_prev_month').on('click', function(){
      if(!$(this).prop('disabled')){
        monthDate.setMonth(monthDate.getMonth() - 1);
        renderCalendar(monthDate, opts);
      }
    });
    calendar.find('#tb_next_month').on('click', function(){
      if(!$(this).prop('disabled')){
        monthDate.setMonth(monthDate.getMonth() + 1);
        renderCalendar(monthDate, opts);
      }
    });
    var prevMonthDate = new Date(year, month - 1, 1);
    if(prevMonthDate < new Date(calendarStartDate.getFullYear(), calendarStartDate.getMonth(), 1)){
      calendar.find('#tb_prev_month').prop('disabled', true);
    }
    var nextMonthDate = new Date(year, month + 1, 1);
    if(nextMonthDate > new Date(calendarEndDate.getFullYear(), calendarEndDate.getMonth(), 1)){
      calendar.find('#tb_next_month').prop('disabled', true);
    }
  }

  window.tbCalendar = {
    formatDate: formatDate,
    renderSlotsForDate: renderSlotsForDate,
    renderCalendar: renderCalendar
  };
})(jQuery);

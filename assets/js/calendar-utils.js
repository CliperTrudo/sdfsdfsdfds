(function($){
  function formatDate(date) {
    var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();
    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;
    return [year, month, day].join('-');
  }

  function renderSlotsForDate(date) {
    var overlay = $('#tb_slots_overlay');
    var slotsContainer = $('#tb_slots_container');
    slotsContainer.empty();
    var slotsForThisDate = window.slotsByDate[date];

    if (slotsForThisDate && slotsForThisDate.length > 0) {
      var dayHtml = '<button type="button" id="tb_close_slots" class="tb-close-btn">&times;</button>';
      dayHtml += '<div class="tb-day-card"><h4>' + date + '</h4><div class="tb-time-slots-list">';
      $.each(slotsForThisDate, function(idx, slot){
        dayHtml += '<label class="tb-time-slot-label">';
        dayHtml += '<input type="radio" name="selected_slot" value="' + slot.start_time + '-' + slot.end_time + '" data-start="' + slot.start_time + '" data-end="' + slot.end_time + '" data-date="' + slot.date + '">';
        dayHtml += slot.start_time + ' - ' + slot.end_time;
        dayHtml += '</label>';
      });
      dayHtml += '</div></div>';
      slotsContainer.html(dayHtml);
    } else {
      slotsContainer.html('<button type="button" id="tb_close_slots" class="tb-close-btn">&times;</button><p class="tb-message tb-message-info">No hay disponibilidad para la fecha seleccionada.</p>');
    }

    overlay.show().css('display','flex');

    overlay.off('click').on('click', function(e){
      if (e.target.id === 'tb_slots_overlay') overlay.hide();
    });
    $('#tb_close_slots').on('click', function(){ overlay.hide(); });

    $('input[name="selected_slot"]').change(function(){
      if (typeof window.tbOnSlotSelected === 'function') {
        window.tbOnSlotSelected($(this));
      }
      overlay.hide();
    });
  }

  function renderCalendar(monthDate) {
    var calendar = $(window.tbCalendarSelector || '#tb_calendar');
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
    $.each(dayNames, function(_, dn){
      calendarHtml += '<div class="tb-calendar-week-day">' + dn + '</div>';
    });
    calendarHtml += '</div>';

    calendarHtml += '<div class="tb-calendar-days">';
    var firstDayIndex = new Date(year, month, 1).getDay();
    for (var i = 0; i < firstDayIndex; i++) {
      calendarHtml += '<div class="tb-calendar-day tb-empty"></div>';
    }
    var daysInMonth = new Date(year, month + 1, 0).getDate();
    for (var d = 1; d <= daysInMonth; d++) {
      var dateObj = new Date(year, month, d);
      var dateStr = formatDate(dateObj);
      if (dateObj < window.calendarStartDate || dateObj > window.calendarEndDate) {
        calendarHtml += '<div class="tb-calendar-day tb-out-of-range">' + d + '</div>';
      } else {
        var available = !!window.slotsByDate[dateStr];
        var classes = 'tb-calendar-day' + (available ? ' tb-day-available' : ' tb-day-unavailable');
        if (window.selectedDate === dateStr) classes += ' tb-selected';
        calendarHtml += '<div class="' + classes + '" data-date="' + dateStr + '">' + d + '</div>';
      }
    }
    calendarHtml += '</div></div>';

    calendar.html(calendarHtml);

    calendar.off('click', '.tb-day-available').on('click', '.tb-day-available', function(){
      window.selectedDate = $(this).data('date');
      $('.tb-calendar-day', calendar).removeClass('tb-selected');
      $(this).addClass('tb-selected');
      if (typeof window.tbOnDaySelected === 'function') {
        window.tbOnDaySelected(window.selectedDate);
      }
    });

    $('#tb_prev_month').on('click', function(){
      if (!$(this).prop('disabled')) {
        monthDate.setMonth(monthDate.getMonth() - 1);
        window.currentMonthDate = new Date(monthDate);
        renderCalendar(window.currentMonthDate);
      }
    });

    $('#tb_next_month').on('click', function(){
      if (!$(this).prop('disabled')) {
        monthDate.setMonth(monthDate.getMonth() + 1);
        window.currentMonthDate = new Date(monthDate);
        renderCalendar(window.currentMonthDate);
      }
    });

    var prevMonthDate = new Date(year, month - 1, 1);
    if (prevMonthDate < new Date(window.calendarStartDate.getFullYear(), window.calendarStartDate.getMonth(), 1)) {
      $('#tb_prev_month').prop('disabled', true);
    }
    var nextMonthDate = new Date(year, month + 1, 1);
    if (nextMonthDate > new Date(window.calendarEndDate.getFullYear(), window.calendarEndDate.getMonth(), 1)) {
      $('#tb_next_month').prop('disabled', true);
    }
  }

  window.tbCalendarUtils = {
    formatDate: formatDate,
    renderSlotsForDate: renderSlotsForDate,
    renderCalendar: renderCalendar
  };
})(jQuery);

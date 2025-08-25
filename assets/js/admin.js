jQuery(function($){
    if (!$('#tb-calendar').length) {
        return;
    }

    var existingDates = Array.isArray(window.tbExistingAvailabilityDates) ? window.tbExistingAvailabilityDates : [];
    var existingSlots = window.tbExistingAvailabilitySlots || {};
    var selected = [];

    function addSlot(start, end) {
        start = start || '';
        end = end || '';
        var container = $('#tb-time-slots');
        var slot = $('<div class="tb-time-slot">'
            + '<label>Inicio</label><input type="time" name="tb_start_time[]" value="'+start+'" required>'
            + '<label>Fin</label><input type="time" name="tb_end_time[]" value="'+end+'" required>'
            + '<button type="button" class="tb-button tb-add-slot">+</button>'
            + '</div>');
        if (container.find('.tb-add-slot').length) {
            container.find('.tb-add-slot').last().removeClass('tb-add-slot').addClass('tb-remove-slot').text('-');
        }
        container.append(slot);
    }

    $('#tb-time-slots').on('click', '.tb-add-slot', function(){
        addSlot();
    });

    $('#tb-time-slots').on('click', '.tb-remove-slot', function(){
        $(this).closest('.tb-time-slot').remove();
        if ($('#tb-time-slots .tb-add-slot').length === 0) {
            $('#tb-time-slots .tb-time-slot').last().find('button').removeClass('tb-remove-slot').addClass('tb-add-slot').text('+');
        }
    });

    var startDate = new Date(); startDate.setHours(0,0,0,0);
    var endDate = new Date(); endDate.setMonth(endDate.getMonth() + 3); endDate.setHours(0,0,0,0);
    var current = new Date(startDate.getFullYear(), startDate.getMonth(), 1);

    function formatDate(d) {
        var month = '' + (d.getMonth() + 1);
        var day = '' + d.getDate();
        var year = d.getFullYear();
        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;
        return [year, month, day].join('-');
    }

    function refreshSelected() {
        var list = $('#tb-selected-dates').empty();
        var hidden = $('#tb-hidden-dates').empty();
        selected.sort();
        selected.forEach(function(d){
            list.append('<li>' + d + '</li>');
            hidden.append('<input type="hidden" name="tb_dates[]" value="' + d + '">');
        });
    }

    function renderCalendar(monthDate) {
        var calendar = $('#tb-calendar');
        var month = monthDate.getMonth();
        var year = monthDate.getFullYear();
        var monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        var dayNames = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

        var html = '<div class="tb-calendar-month">';
        html += '<div class="tb-calendar-nav">';
        var prevDisabled = (monthDate.getFullYear() === startDate.getFullYear() && monthDate.getMonth() <= startDate.getMonth());
        var nextDisabled = (monthDate.getFullYear() === endDate.getFullYear() && monthDate.getMonth() >= endDate.getMonth());
        html += '<button id="tb_prev_month" class="tb-nav-btn"' + (prevDisabled ? ' disabled' : '') + '>&lt;</button>';
        html += '<span class="tb-calendar-month-name">' + monthNames[month] + ' ' + year + '</span>';
        html += '<button id="tb_next_month" class="tb-nav-btn"' + (nextDisabled ? ' disabled' : '') + '>&gt;</button>';
        html += '</div>';

        html += '<div class="tb-calendar-week-day-names">';
        dayNames.forEach(function(d){ html += '<div class="tb-calendar-week-day">' + d + '</div>'; });
        html += '</div>';

        html += '<div class="tb-calendar-days">';
        var firstDayIndex = new Date(year, month, 1).getDay();
        for (var i=0; i<firstDayIndex; i++) {
            html += '<div class="tb-calendar-day tb-empty"></div>';
        }
        var daysInMonth = new Date(year, month + 1, 0).getDate();
        for (var d=1; d<=daysInMonth; d++) {
            var dateObj = new Date(year, month, d);
            var dateStr = formatDate(dateObj);
            var classes = 'tb-calendar-day';
            var inner = '<span class="tb-day-number">'+d+'</span>';
            if (dateObj < startDate || dateObj > endDate) {
                classes += ' tb-day-unavailable';
            } else if (existingDates.indexOf(dateStr) !== -1) {
                classes += ' tb-day-unavailable tb-day-has-availability';
                inner += '<span class="tb-day-dots">...</span>';
            } else {
                classes += ' tb-day-available';
                if (selected.indexOf(dateStr) !== -1) {
                    classes += ' tb-selected';
                }
            }
            html += '<div class="' + classes + '" data-date="' + dateStr + '">' + inner + '</div>';
        }
        html += '</div></div>';
        calendar.html(html);
    }

    $('#tb-calendar').on('click', '.tb-calendar-day.tb-day-available', function(){
        var date = $(this).data('date');
        var idx = selected.indexOf(date);
        if (idx > -1) {
            selected.splice(idx,1);
        } else {
            selected.push(date);
        }
        renderCalendar(current);
        refreshSelected();
    });

    $('#tb-calendar').on('click', '#tb_prev_month', function(){
        if ($(this).prop('disabled')) return;
        current.setMonth(current.getMonth() - 1);
        renderCalendar(current);
    });

    $('#tb-calendar').on('click', '#tb_next_month', function(){
        if ($(this).prop('disabled')) return;
        current.setMonth(current.getMonth() + 1);
        renderCalendar(current);
    });

    $('#tb-calendar').on('click', '.tb-day-dots', function(e){
        e.stopPropagation();
        $('#tb-day-menu').remove();
        var date = $(this).closest('.tb-day-has-availability').data('date');
        var offset = $(this).offset();
        var menu = $('<div id="tb-day-menu" class="tb-day-menu"><button type="button" class="tb-day-info">Información</button><button type="button" class="tb-day-edit">Editar</button></div>');
        menu.css({top: offset.top + $(this).height(), left: offset.left});
        menu.data('date', date);
        $('body').append(menu);
    });

    $('body').on('click', function(){ $('#tb-day-menu').remove(); });

    $('body').on('click', '#tb-day-menu .tb-day-info', function(e){
        e.stopPropagation();
        var menu = $('#tb-day-menu');
        var date = menu.data('date');
        var slots = existingSlots[date] || [];
        alert('Disponibilidad de ' + date + ':\n' + (slots.join('\n') || 'Sin tramos'));
        menu.remove();
    });

    function loadSlots(slots) {
        var container = $('#tb-time-slots').empty();
        if (!slots.length) {
            addSlot();
        } else {
            slots.forEach(function(s){
                var parts = s.split('-');
                addSlot(parts[0], parts[1]);
            });
        }
    }

    $('body').on('click', '#tb-day-menu .tb-day-edit', function(e){
        e.stopPropagation();
        var menu = $('#tb-day-menu');
        var date = menu.data('date');
        var slots = existingSlots[date] || [];
        selected = [date];
        loadSlots(slots);
        renderCalendar(current);
        refreshSelected();
        menu.remove();
    });

    renderCalendar(current);
    refreshSelected();
});


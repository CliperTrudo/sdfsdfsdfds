jQuery(function($){
    if (!$('#tb-calendar').length) {
        return;
    }

    // Add/remove time ranges
    $('#tb-time-ranges').on('click', '.tb-add-range', function(){
        var $clone = $(this).closest('.tb-time-range').clone();
        $clone.find('input').val('');
        $clone.find('.tb-add-range').remove();
        $clone.append('<button type="button" class="tb-button tb-remove-range">-</button>');
        $('#tb-time-ranges').append($clone);
    });

    $('#tb-time-ranges').on('click', '.tb-remove-range', function(){
        $(this).closest('.tb-time-range').remove();
    });

    var existing = Array.isArray(window.tbExistingAvailabilityDates) ? window.tbExistingAvailabilityDates : [];
    var selected = [];
    if (window.tbEditingDate) {
        selected.push(window.tbEditingDate);
    }
    var startDate = new Date();
    startDate.setHours(0,0,0,0);
    var endDate = new Date();
    endDate.setMonth(endDate.getMonth() + 3);
    endDate.setHours(0,0,0,0);
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
            if (dateObj < startDate || dateObj > endDate) {
                classes += ' tb-day-unavailable';
                html += '<div class="' + classes + '"><span class="tb-day-number">' + d + '</span></div>';
                continue;
            }
            if (existing.indexOf(dateStr) !== -1) {
                classes += ' tb-day-unavailable';
                var cell = '<div class="' + classes + '" data-date="' + dateStr + '">';
                cell += '<span class="tb-day-number">' + d + '</span>';
                cell += '<button type="button" class="tb-day-menu-btn">⋮</button>';
                cell += '<div class="tb-day-menu" data-date="' + dateStr + '">';
                cell += '<button type="button" class="tb-view">Ver disponibilidad</button>';
                cell += '<button type="button" class="tb-edit">Editar disponibilidad</button>';
                cell += '</div></div>';
                html += cell;
            } else {
                classes += ' tb-day-available';
                if (selected.indexOf(dateStr) !== -1) {
                    classes += ' tb-selected';
                }
                html += '<div class="' + classes + '" data-date="' + dateStr + '"><span class="tb-day-number">' + d + '</span></div>';
            }
        }
        html += '</div></div>';
        calendar.html(html);
    }

    $('#tb-calendar').on('click', '.tb-calendar-day.tb-day-available', function(){
        var date = $(this).data('date');
        var idx = selected.indexOf(date);
        if (idx > -1) {
            selected.splice(idx,1);
            $(this).removeClass('tb-selected');
        } else {
            selected.push(date);
            $(this).addClass('tb-selected');
        }
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

    // Menu actions
    $('#tb-calendar').on('click', '.tb-day-menu-btn', function(e){
        e.stopPropagation();
        var $menu = $(this).siblings('.tb-day-menu');
        $('.tb-day-menu').not($menu).hide();
        $menu.toggle();
    });

    $(document).on('click', function(){
        $('.tb-day-menu').hide();
    });

    $('#tb-calendar').on('click', '.tb-day-menu .tb-view', function(){
        var date = $(this).parent().data('date');
        $.post(ajaxurl, {action: 'tb_get_day_availability', tutor_id: window.tbTutorId, date: date}, function(res){
            if (res.success) {
                alert(res.data.join('\n'));
            } else {
                alert(res.data || 'Error al obtener la disponibilidad');
            }
        });
    });

    $('#tb-calendar').on('click', '.tb-day-menu .tb-edit', function(){
        var date = $(this).parent().data('date');
        var url = new URL(window.location.href);
        url.searchParams.set('edit_date', date);
        window.location.href = url.toString();
    });

    // Prefill ranges when editing
    if (Array.isArray(window.tbEditingRanges) && window.tbEditingRanges.length > 0) {
        $('#tb-time-ranges').empty();
        window.tbEditingRanges.forEach(function(r, idx){
            var html = '<div class="tb-time-range">';
            html += '<label>Inicio</label>';
            html += '<input type="time" name="tb_start_time[]" value="' + r.start + '" required>';
            html += '<label>Fin</label>';
            html += '<input type="time" name="tb_end_time[]" value="' + r.end + '" required>';
            if (idx === window.tbEditingRanges.length - 1) {
                html += '<button type="button" class="tb-button tb-add-range">+</button>';
            } else {
                html += '<button type="button" class="tb-button tb-remove-range">-</button>';
            }
            html += '</div>';
            $('#tb-time-ranges').append(html);
        });
    }

    renderCalendar(current);
    refreshSelected();
});

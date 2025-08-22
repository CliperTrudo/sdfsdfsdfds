jQuery(function($){
    if (!$('#tb-calendar').length) {
        return;
    }

    var existing = Array.isArray(window.tbExistingAvailabilityDates) ? window.tbExistingAvailabilityDates : [];
    var selected = [];
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
            if (dateObj < startDate || dateObj > endDate || existing.indexOf(dateStr) !== -1) {
                classes += ' tb-day-unavailable';
            } else {
                classes += ' tb-day-available';
                if (selected.indexOf(dateStr) !== -1) {
                    classes += ' tb-selected';
                }
            }
            html += '<div class="' + classes + '" data-date="' + dateStr + '">' + d + '</div>';
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

    renderCalendar(current);
    refreshSelected();
});


// admin js
jQuery(function($){
    var $cal = $('#tb-calendar');
    if (!$cal.length || typeof $.fn.datepicker !== 'function') {
        return;
    }

    var dates = [];
    var unavailable = window.tbExistingAvailabilityDates || [];
    $cal.datepicker({
        dateFormat: 'yy-mm-dd',
        beforeShowDay: function(date) {
            var d = $.datepicker.formatDate('yy-mm-dd', date);
            var isUnavailable = unavailable.indexOf(d) >= 0;
            return [!isUnavailable, isUnavailable ? 'tb-date-unavailable' : ''];
        },
        onSelect: function(dateText) {
            if (unavailable.indexOf(dateText) >= 0) {
                return;
            }
            var idx = dates.indexOf(dateText);
            if (idx >= 0) {
                dates.splice(idx, 1);
            } else {
                dates.push(dateText);
            }
            render();
        }
    });

    function render() {
        var $list = $('#tb-selected-dates').empty();
        var $hidden = $('#tb-hidden-dates').empty();
        dates.forEach(function(d){
            $list.append('<li>'+d+'</li>');
            $hidden.append('<input type="hidden" name="tb_dates[]" value="'+d+'">');
        });
    }
});

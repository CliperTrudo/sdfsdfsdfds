// admin js
jQuery(function($){
    var $cal = $('#tb-calendar');
    if (!$cal.length || typeof $.fn.datepicker !== 'function') {
        return;
    }

    var dates = [];
    $cal.datepicker({
        dateFormat: 'yy-mm-dd',
        onSelect: function(dateText) {
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

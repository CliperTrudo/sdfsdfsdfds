jQuery(document).ready(function($) {
  var ajaxurl = tbBooking.ajaxUrl;
  var slotsByDate = {};           // Franjas horarias agrupadas por fecha
  var allSortedDates = [];        // Todas las fechas ordenadas
  var calendarStartDate, calendarEndDate, currentMonthDate, selectedDate;

  // Formatea fecha a YYYY-MM-DD
  function formatDate(date) {
    var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;

    return [year, month, day].join('-');
  }

  // Paso 1: verificación de DNI y correo
  $('#tb_dni_form').submit(function(e) {
    e.preventDefault();
    var dni   = $('#tb_dni').val().trim();
    var email = $('#tb_email').val().trim();
    var nonce = $('#tb_booking_nonce_field').val();

    var recaptcha = '';
    if (typeof grecaptcha !== 'undefined') {
      recaptcha = grecaptcha.getResponse();
      if (!recaptcha) {
        $('#tb_dni_message').removeClass('tb-hidden').addClass('tb-message-error').text('Por favor, completa el reCAPTCHA.');
        return;
      }
    }

    $('#tb_dni_message').addClass('tb-hidden').removeClass('tb-message-error').text('');

    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        action: 'tb_verify_dni',
        dni: dni,
        email: email,
        nonce: nonce,
        'g-recaptcha-response': recaptcha
      },
      success: function(response) {
        if (response.success) {
          $('#tb_dni_verified').val(dni);
          $('#tb_email_verified').val(email);
          $('#tb_dni_step').fadeOut(function() {
            $('#tb_exam_date_step').removeClass('tb-hidden').hide().fadeIn();
          });
        } else {
          $('#tb_dni_message').removeClass('tb-hidden').addClass('tb-message-error').text(response.data);
        }
      },
      error: function() {
        $('#tb_dni_message').removeClass('tb-hidden').addClass('tb-message-error').text('Error en la verificación.');
      },
      complete: function() {
        if (typeof grecaptcha !== 'undefined') {
          grecaptcha.reset();
        }
      }
    });
  });

  // Botones para volver al paso anterior
  $('#tb_back_to_dni').on('click', function() {
    $('#tb_exam_date_step').fadeOut(function() {
      $('#tb_dni_step').fadeIn();
    });
  });

  $('#tb_back_to_exam_date').on('click', function() {
    $('#tb_tutor_selection_step').fadeOut(function() {
      $('#tb_exam_date_step').fadeIn();
    });
  });

  // Paso 2: selección de fecha de examen
  $('#tb_exam_date_form').submit(function(e) {
    e.preventDefault();
    var examDate = $('#tb_exam_date').val();
    var currentDate = formatDate(new Date());

    $('#tb_exam_date_message').addClass('tb-hidden').removeClass('tb-message-error').text('');

    if (!examDate || examDate < currentDate) {
      $('#tb_exam_date_message').removeClass('tb-hidden').addClass('tb-message-error').text('La fecha del examen no puede ser anterior a hoy.');
      return;
    }

    var dni   = $('#tb_dni_verified').val();
    var email = $('#tb_email_verified').val();
    $('#tb_dni_final').val(dni);
    $('#tb_email_final').val(email);
    $('#tb_exam_date_final').val(examDate);
    $('#tb_summary').html('<strong>Fecha de Examen:</strong> ' + examDate);

    $('#tb_exam_date_step').fadeOut(function() {
      $('#tb_tutor_selection_step').removeClass('tb-hidden').hide().fadeIn();
    });
  });

  // Renderiza las franjas horarias para una fecha en un modal flotante
  function renderSlotsForDate(date) {
    var overlay = $('#tb_slots_overlay');
    var slotsContainer = $('#tb_slots_container');
    slotsContainer.empty();
    var slotsForThisDate = slotsByDate[date];

    if (slotsForThisDate && slotsForThisDate.length > 0) {
      var dayHtml = '<button type="button" id="tb_close_slots" class="tb-close-btn">&times;</button>';
      dayHtml += '<div class="tb-day-card">';
      dayHtml += '<h4>' + date + '</h4>';
      dayHtml += '<div class="tb-time-slots-list">';

      $.each(slotsForThisDate, function(idx, slot) {
        dayHtml += '<label class="tb-time-slot-label">';
        dayHtml += '<input type="radio" name="selected_slot" value="' + slot.start_time + '-' + slot.end_time + '" data-start="' + slot.start_time + '" data-end="' + slot.end_time + '" data-date="' + slot.date + '">';
        dayHtml += slot.start_time + ' - ' + slot.end_time;
        dayHtml += '</label>';
      });

      dayHtml += '</div>';
      dayHtml += '</div>';
      slotsContainer.html(dayHtml);
    } else {
      slotsContainer.html('<button type="button" id="tb_close_slots" class="tb-close-btn">&times;</button><p class="tb-message tb-message-info">No hay disponibilidad para la fecha seleccionada.</p>');
    }

    overlay.show().css('display', 'flex');

    // Cerrar al hacer click fuera del contenido
    overlay.off('click').on('click', function(e) {
      if (e.target.id === 'tb_slots_overlay') overlay.hide();
    });

    // Botón cerrar
    $('#tb_close_slots').on('click', function() {
      overlay.hide();
    });

    // Selección de franja
    $('input[name="selected_slot"]').change(function() {
      $('#tb_submit_booking').prop('disabled', false);
      $('#tb_selected_slot').text('Seleccionado: ' + $(this).data('date') + ' ' + $(this).data('start') + ' - ' + $(this).data('end'));
      overlay.hide();
    });
  }

  // Calendario con navegación por mes
  function renderCalendar(monthDate) {
    var calendar = $('#tb_calendar');
    var monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    var dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    var month = monthDate.getMonth();
    var year = monthDate.getFullYear();

    var calendarHtml = '<div class="tb-calendar-month">';
    calendarHtml += '<div class="tb-calendar-nav">';
    calendarHtml += '<button id="tb_prev_month" class="tb-nav-btn">&lt;</button>';
    calendarHtml += '<span class="tb-calendar-month-name">' + monthNames[month] + ' ' + year + '</span>';
    calendarHtml += '<button id="tb_next_month" class="tb-nav-btn">&gt;</button>';
    calendarHtml += '</div>';

    // Nombres de días
    calendarHtml += '<div class="tb-calendar-week-day-names">';
    $.each(dayNames, function(_, dn) {
      calendarHtml += '<div class="tb-calendar-week-day">' + dn + '</div>';
    });
    calendarHtml += '</div>';

    // Días del mes
    calendarHtml += '<div class="tb-calendar-days">';
    var firstDayIndex = new Date(year, month, 1).getDay();
    for (var i = 0; i < firstDayIndex; i++) {
      calendarHtml += '<div class="tb-calendar-day tb-empty"></div>';
    }
    var daysInMonth = new Date(year, month + 1, 0).getDate();
    for (var d = 1; d <= daysInMonth; d++) {
      var dateObj = new Date(year, month, d);
      var dateStr = formatDate(dateObj);
      if (dateObj < calendarStartDate || dateObj > calendarEndDate) {
        calendarHtml += '<div class="tb-calendar-day tb-out-of-range">' + d + '</div>';
      } else {
        var available = !!slotsByDate[dateStr];
        var classes = 'tb-calendar-day' + (available ? ' tb-day-available' : ' tb-day-unavailable');
        if (selectedDate === dateStr) classes += ' tb-selected';
        calendarHtml += '<div class="' + classes + '" data-date="' + dateStr + '">' + d + '</div>';
      }
    }
    calendarHtml += '</div></div>';

    calendar.html(calendarHtml);

    // Click en días disponibles -> abre modal de slots
    $('.tb-day-available').on('click', function() {
      selectedDate = $(this).data('date');
      $('.tb-calendar-day').removeClass('tb-selected');
      $(this).addClass('tb-selected');
      $('#tb_submit_booking').prop('disabled', true);
      renderSlotsForDate(selectedDate);
    });

    // Navegación de meses
    $('#tb_prev_month').on('click', function() {
      if (!$(this).prop('disabled')) {
        currentMonthDate.setMonth(currentMonthDate.getMonth() - 1);
        renderCalendar(currentMonthDate);
      }
    });

    $('#tb_next_month').on('click', function() {
      if (!$(this).prop('disabled')) {
        currentMonthDate.setMonth(currentMonthDate.getMonth() + 1);
        renderCalendar(currentMonthDate);
      }
    });

    // Deshabilitar navegación fuera de rango (comparando por mes)
    var prevMonthDate = new Date(year, month - 1, 1);
    if (prevMonthDate < new Date(calendarStartDate.getFullYear(), calendarStartDate.getMonth(), 1)) {
      $('#tb_prev_month').prop('disabled', true);
    }
    var nextMonthDate = new Date(year, month + 1, 1);
    if (nextMonthDate > new Date(calendarEndDate.getFullYear(), calendarEndDate.getMonth(), 1)) {
      $('#tb_next_month').prop('disabled', true);
    }
  }

  // Cambio de tutor
  $('#tb_tutor_select').change(function() {
    var tutor_id = $(this).val();
    var exam_date = $('#tb_exam_date_final').val(); // Fecha de examen seleccionada por el usuario

    var today = new Date();
    var current_date_for_query = formatDate(today);

    // Fin del rango = 5 días antes del examen
    var exam_date_obj = new Date(exam_date + 'T00:00:00');
    exam_date_obj.setDate(exam_date_obj.getDate() - 5);
    var end_date_for_query = formatDate(exam_date_obj);

    var nonce = $('#tb_booking_nonce_field').val(); // Nonce
    var calendarContainer = $('#tb_calendar_container');

    $('#tb_submit_booking').prop('disabled', true);
    $('#tb_response_message').hide();
    calendarContainer.html('<p class="tb-message tb-message-info">Cargando franjas horarias...</p>');

    if (tutor_id) {
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'tb_get_available_slots',
          tutor_id: tutor_id,
          start_date: current_date_for_query,
          end_date: end_date_for_query,
          nonce: nonce
        },
        success: function(response) {
          if (response.success) {
            if (response.data && response.data.length > 0) {
              // Mapear por fecha
              slotsByDate = {};
              $.each(response.data, function(index, slot) {
                if (!slotsByDate[slot.date]) {
                  slotsByDate[slot.date] = [];
                }
                slotsByDate[slot.date].push(slot);
              });

              allSortedDates = Object.keys(slotsByDate).sort();

              // Definir rango del calendario y mes inicial
              calendarStartDate = new Date(current_date_for_query + 'T00:00:00');
              calendarEndDate   = new Date(end_date_for_query + 'T00:00:00');

              if (allSortedDates.length > 0) {
                currentMonthDate = new Date(allSortedDates[0] + 'T00:00:00');
              } else {
                currentMonthDate = new Date(calendarStartDate);
              }

              selectedDate = null; // No preseleccionar fecha

              // Pintar calendario + texto del slot seleccionado
              calendarContainer.html('<div id="tb_calendar"></div>');
              $('#tb_selected_slot').text('');

              // Crear overlay si no existe
              if (!$('#tb_slots_overlay').length) {
                $('body').append('<div id="tb_slots_overlay" class="tb-slots-overlay" style="display:none"><div id="tb_slots_container" class="tb-slots-content"></div></div>');
              }

              renderCalendar(currentMonthDate);
            } else {
              calendarContainer.html('<p class="tb-message tb-message-info">No se encontraron franjas de tiempo disponibles para este tutor en el rango de fechas seleccionado.</p>');
            }
          } else {
            $('#tb_response_message').html('<p class="tb-message tb-message-error">Error al obtener la disponibilidad: ' + (response.data || 'Error desconocido') + '</p>').show();
            calendarContainer.html('<p class="tb-message tb-message-info">Selecciona un tutor para ver las franjas horarias disponibles.</p>');
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
          console.error('AJAX Error:', textStatus, errorThrown, jqXHR);
          $('#tb_response_message').html('<p class="tb-message tb-message-error">Error en la solicitud AJAX: ' + textStatus + ' - ' + errorThrown + '</p>').show();
          calendarContainer.html('<p class="tb-message tb-message-info">Selecciona un tutor para ver las franjas horarias disponibles.</p>');
        }
      });
    } else {
      calendarContainer.html('<p class="tb-message tb-message-info">Selecciona un tutor para ver las franjas horarias disponibles.</p>');
      $('#tb_response_message').hide();
    }
  });

  // Envío del formulario de reserva
  $('#tb_booking_form').submit(function(e) {
    e.preventDefault();

    var selectedSlot = $('input[name="selected_slot"]:checked');
    if (selectedSlot.length === 0) {
      $('#tb_response_message').html('<p class="tb-message tb-message-error">Por favor, selecciona una franja horaria.</p>').show();
      return;
    }

    var tutor_id   = $('#tb_tutor_select').val();
    var dni        = $('#tb_dni_final').val();
    var email      = $('#tb_email_final').val();
    var exam_date  = selectedSlot.data('date');
    var start_time = selectedSlot.data('start');
    var end_time   = selectedSlot.data('end');
    var nonce      = $('#tb_booking_nonce_field').val();

    $('#tb_submit_booking').prop('disabled', true).val('Procesando...');
    $('#tb_response_message').html('<p class="tb-message tb-message-info">Procesando tu reserva, por favor espera...</p>').show();

    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        action: 'tb_process_booking',
        dni: dni,
        email: email,
        tutor_id: tutor_id,
        exam_date: exam_date,
        start_time: start_time,
        end_time: end_time,
        nonce: nonce
      },
      success: function(response) {
        if (response.success) {
          var messageHtml = '<p class="tb-message tb-message-success">' + response.data.message + '</p>';

          if (response.data.day_of_week) {
            messageHtml += '<p>Día: ' + response.data.day_of_week + '</p>';
          }

          if (response.data.start_datetime_utc && response.data.end_datetime_utc) {
            var startLocal = new Date(response.data.start_datetime_utc)
              .toLocaleString('es-ES', { timeZone: 'Europe/Madrid' });
            var endLocal = new Date(response.data.end_datetime_utc)
              .toLocaleString('es-ES', { timeZone: 'Europe/Madrid' });
            messageHtml += '<p>Fecha: ' + startLocal + ' - ' + endLocal + '</p>';
          } else if (response.data.exam_date && response.data.start_time && response.data.end_time) {
            messageHtml += '<p>Fecha: ' + response.data.exam_date + ' de ' + response.data.start_time + ' a ' + response.data.end_time + '</p>';
          }

          if (response.data.student_first_name) {
            var apellido = response.data.student_last_name ? ' ' + response.data.student_last_name : '';
            messageHtml += '<p>Alumno: ' + response.data.student_first_name + apellido + '</p>';
          }

          if (response.data.meet_link) {
            messageHtml += '<p>Enlace de Google Meet: <a href="' + response.data.meet_link + '" target="_blank" style="color: #0073aa; text-decoration: underline;">' + response.data.meet_link + '</a></p>';
          }

          var cardHtml = '<div class="tb-booking-card">' + messageHtml + '</div>';
          $('#tb_booking_details_container').html(cardHtml);
          $('#tb_response_message').hide();
          $('#tb_booking_form').hide();

          // Limpiar el formulario para evitar nuevas selecciones
          $('#tb_booking_form')[0].reset();
          $('#tb_submit_booking').prop('disabled', true).val('Confirmar Reserva');
        } else {
          $('#tb_response_message').html('<p class="tb-message tb-message-error">Error: ' + (response.data || 'Error desconocido al procesar la reserva.') + '</p>').show();
          $('#tb_submit_booking').prop('disabled', false).val('Confirmar Reserva');
        }
      },
      error: function(jqXHR, textStatus, errorThrown) {
        console.error('AJAX Error:', textStatus, errorThrown, jqXHR);
        $('#tb_response_message').html('<p class="tb-message tb-message-error">Error en la solicitud AJAX: ' + textStatus + ' - ' + errorThrown + '</p>').show();
        $('#tb_submit_booking').prop('disabled', false).val('Confirmar Reserva');
      }
    });
  });
});

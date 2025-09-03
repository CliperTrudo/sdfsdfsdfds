<?php
namespace TutoriasBooking\Google;

class CalendarService {
    public static function get_calendar_service($tutor_id) {
        $client = GoogleClient::get_client();
        $tokens = GoogleClient::get_tokens($tutor_id);
        if (!$tokens) { return null; }
        $client->setAccessToken($tokens);
        if ($client->isAccessTokenExpired()) {
            if (isset($tokens['refresh_token']) && !empty($tokens['refresh_token'])) {
                $new = GoogleClient::refresh_access_token($client, $tutor_id, $tokens['refresh_token']);
                if (!$new) { return null; }
            } else {
                return null;
            }
        }
        return new \Google_Service_Calendar($client);
    }

    public static function get_calendar_events($tutor_id, $start_date, $end_date) {
        global $wpdb;
        $tutor = $wpdb->get_row($wpdb->prepare("SELECT calendar_id FROM {$wpdb->prefix}tutores WHERE id=%d", $tutor_id));
        if (!$tutor || empty($tutor->calendar_id)) { return []; }
        $service = self::get_calendar_service($tutor_id);
        if (!$service) { return []; }
        $calendarId = $tutor->calendar_id;

        // Convert provided dates from Europe/Madrid to UTC before querying the API
        $madridTz = new \DateTimeZone('Europe/Madrid');
        $utcTz    = new \DateTimeZone('UTC');
        $startObj = new \DateTime($start_date . ' 00:00:00', $madridTz);
        $endObj   = new \DateTime($end_date   . ' 23:59:59', $madridTz);
        $startObj->setTimezone($utcTz);
        $endObj->setTimezone($utcTz);

        $opt = [
            'timeMin' => $startObj->format('c'),
            'timeMax' => $endObj->format('c'),
            'singleEvents' => true,
            'orderBy' => 'startTime'
        ];
        try {
            $events = $service->events->listEvents($calendarId, $opt);
            return $events->getItems();
        } catch (\Exception $e) {
            return [];
        }
    }

    public static function get_available_calendar_events($tutor_id, $start_date, $end_date) {
        $events = self::get_calendar_events($tutor_id, $start_date, $end_date);
        $available = [];
        foreach ($events as $event) {
            if (isset($event->summary) && trim(strtoupper($event->summary)) === 'DISPONIBLE') {
                $available[] = $event;
            }
        }
        return $available;
    }

    public static function get_busy_calendar_events($tutor_id, $start_date, $end_date) {
        $events = self::get_calendar_events($tutor_id, $start_date, $end_date);
        $busy = [];
        foreach ($events as $event) {
            if (!isset($event->summary) || trim(strtoupper($event->summary)) !== 'DISPONIBLE') {
                $busy[] = $event;
            }
        }
        return $busy;
    }

    public static function create_calendar_event($tutor_id, $summary, $description, $start_datetime, $end_datetime, $attendees=[]) {
        global $wpdb;
        $tutor = $wpdb->get_row($wpdb->prepare("SELECT calendar_id FROM {$wpdb->prefix}tutores WHERE id = %d", $tutor_id));
        if (!$tutor || empty($tutor->calendar_id)) {
            return new \WP_Error('missing_calendar_id', 'El tutor no tiene un calendar_id válido.');
        }
        $service = self::get_calendar_service($tutor_id);
        if (!$service) {
            return new \WP_Error('service_unavailable', 'No se pudo obtener el servicio de Google Calendar.');
        }
        $calendarId = $tutor->calendar_id;
        // $start_datetime and $end_datetime are expected to be in UTC
        $event = new \Google_Service_Calendar_Event([
            'summary' => $summary,
            'description' => $description,
            'start' => ['dateTime' => $start_datetime, 'timeZone' => 'UTC'],
            'end'   => ['dateTime' => $end_datetime,   'timeZone' => 'UTC'],
            'attendees' => array_map(fn($e)=>['email'=>$e], $attendees),
            'reminders' => ['useDefault'=>false,'overrides'=>[['method'=>'email','minutes'=>60],['method'=>'popup','minutes'=>10]]],
            'conferenceData' => ['createRequest'=>['requestId'=>uniqid(),'conferenceSolutionKey'=>['type'=>'hangoutsMeet']]]
        ]);
        try {
            return $service->events->insert(
                $calendarId,
                $event,
                [
                    'conferenceDataVersion' => 1,
                    'sendUpdates' => 'all',
                ]
            );
        } catch (\Exception $e) {
            return new \WP_Error('event_creation_failed', $e->getMessage());
        }
    }

    /**
     * Update an existing calendar event.
     */
    public static function update_calendar_event($tutor_id, $event_id, $summary, $description, $start_datetime, $end_datetime, $attendees=[]) {
        global $wpdb;
        $tutor = $wpdb->get_row($wpdb->prepare("SELECT calendar_id FROM {$wpdb->prefix}tutores WHERE id = %d", $tutor_id));
        if (!$tutor || empty($tutor->calendar_id)) {
            return new \WP_Error('missing_calendar_id', 'El tutor no tiene un calendar_id válido.');
        }
        $service = self::get_calendar_service($tutor_id);
        if (!$service) {
            return new \WP_Error('service_unavailable', 'No se pudo obtener el servicio de Google Calendar.');
        }
        $event = new \Google_Service_Calendar_Event([
            'summary' => $summary,
            'description' => $description,
            'start' => ['dateTime' => $start_datetime, 'timeZone' => 'UTC'],
            'end'   => ['dateTime' => $end_datetime,   'timeZone' => 'UTC'],
            'attendees' => array_map(fn($e)=>['email'=>$e], $attendees),
        ]);
        try {
            return $service->events->update(
                $tutor->calendar_id,
                $event_id,
                $event,
                [
                    'sendUpdates' => 'all',
                ]
            );
        } catch (\Exception $e) {
            return new \WP_Error('event_update_failed', $e->getMessage());
        }
    }

    /**
     * Delete a specific calendar event.
     */
    public static function delete_calendar_event($tutor_id, $event_id) {
        global $wpdb;
        $tutor = $wpdb->get_row($wpdb->prepare("SELECT calendar_id FROM {$wpdb->prefix}tutores WHERE id = %d", $tutor_id));
        if (!$tutor || empty($tutor->calendar_id)) {
            return 0;
        }
        $service = self::get_calendar_service($tutor_id);
        if (!$service) {
            return 0;
        }
        try {
            $service->events->delete($tutor->calendar_id, $event_id, ['sendUpdates' => 'all']);
            return 1;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Delete all "DISPONIBLE" events for a specific date.
     */
    public static function delete_available_events_for_date($tutor_id, $date) {
        global $wpdb;
        $tutor = $wpdb->get_row($wpdb->prepare("SELECT calendar_id FROM {$wpdb->prefix}tutores WHERE id = %d", $tutor_id));
        if (!$tutor || empty($tutor->calendar_id)) { return 0; }
        $service = self::get_calendar_service($tutor_id);
        if (!$service) { return 0; }
        $events = self::get_available_calendar_events($tutor_id, $date, $date);
        $deleted = 0;
        foreach ($events as $event) {
            try {
                $service->events->delete($tutor->calendar_id, $event->id, ['sendUpdates' => 'all']);
                $deleted++;
            } catch (\Exception $e) {
                // Ignore individual deletion errors
            }
        }
        return $deleted;
    }

    /**
     * Get all calendar events containing the provided DNI.
     */
    public static function get_events_by_dni($dni) {
        global $wpdb;
        $found = [];
        $tutors = $wpdb->get_results("SELECT id, calendar_id FROM {$wpdb->prefix}tutores");
        foreach ($tutors as $tutor) {
            if (empty($tutor->calendar_id)) { continue; }
            $service = self::get_calendar_service($tutor->id);
            if (!$service) { continue; }
            $opt = [
                'q'            => $dni,
                'singleEvents' => true,
                'maxResults'   => 2500,
                'orderBy'      => 'startTime',
            ];
            try {
                $events = $service->events->listEvents($tutor->calendar_id, $opt);
                foreach ($events->getItems() as $event) {
                    $found[] = $event;
                }
            } catch (\Exception $e) {
                // Ignorar errores al listar eventos
            }
        }
        return $found;
    }

    /**
     * Delete all calendar events containing the provided DNI.
     */
    public static function delete_events_by_dni($dni) {
        global $wpdb;
        $deleted = 0;
        $tutors = $wpdb->get_results("SELECT id, calendar_id FROM {$wpdb->prefix}tutores");
        foreach ($tutors as $tutor) {
            if (empty($tutor->calendar_id)) { continue; }
            $service = self::get_calendar_service($tutor->id);
            if (!$service) { continue; }
            $opt = [
                'q'            => $dni,
                'singleEvents' => true,
                'maxResults'   => 2500,
            ];
            try {
                $events = $service->events->listEvents($tutor->calendar_id, $opt);
                foreach ($events->getItems() as $event) {
                    try {
                        $service->events->delete($tutor->calendar_id, $event->id, ['sendUpdates' => 'all']);
                        $deleted++;
                    } catch (\Exception $e) {
                        // Ignorar errores individuales de borrado
                    }
                }
            } catch (\Exception $e) {
                // Ignorar errores al listar eventos
            }
        }
        return $deleted;
    }
}

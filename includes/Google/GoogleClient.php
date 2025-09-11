<?php
namespace TutoriasBooking\Google;

class GoogleClient {
    public static function get_credentials() {
        static $creds = null;
        if ($creds === null) {
            $creds_path = TB_PLUGIN_DIR . 'keys/credentials.json';
            if (!file_exists($creds_path)) {
                wp_die('No se encuentra el archivo de credenciales: ' . esc_html($creds_path));
            }
            $json = file_get_contents($creds_path);
            $creds = json_decode($json, true);
            if (!$creds || empty($creds['web'])) {
                wp_die('Formato inválido de credentials.json.');
            }
        }
        return $creds['web'];
    }

    public static function get_client() {
        $client = new \Google_Client();
        $creds = self::get_credentials();
        $client->setApplicationName('Tutorias Booking');
        $client->setClientId($creds['client_id']);
        $client->setClientSecret($creds['client_secret']);
        $client->setRedirectUri($creds['redirect_uris'][0]);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $client->addScope(\Google_Service_Calendar::CALENDAR_EVENTS);
        $client->addScope(\Google_Service_Calendar::CALENDAR_READONLY);
        $client->addScope('https://www.googleapis.com/auth/meetings');
        $client->addScope(\Google_Service_Oauth2::USERINFO_EMAIL);
        return $client;
    }

    public static function save_tokens($tutor_id, $access_token, $refresh_token, $expires_in) {
        global $wpdb;
        $table = $wpdb->prefix . 'tutores_tokens';
        $expiry_datetime = date('Y-m-d H:i:s', time() + $expires_in);
        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE tutor_id=%d", $tutor_id));
        if ($existing) {
            $wpdb->update(
                $table,
                ['access_token' => $access_token, 'refresh_token' => $refresh_token, 'expiry' => $expiry_datetime],
                ['tutor_id' => $tutor_id],
                ['%s','%s','%s'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $table,
                ['tutor_id'=>$tutor_id,'access_token'=>$access_token,'refresh_token'=>$refresh_token,'expiry'=>$expiry_datetime],
                ['%d','%s','%s','%s']
            );
        }
    }

    public static function get_tokens($tutor_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'tutores_tokens';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE tutor_id=%d", $tutor_id), ARRAY_A);
    }

    public static function refresh_access_token($client, $tutor_id, $refresh_token) {
        try {
            $client->fetchAccessTokenWithRefreshToken($refresh_token);
            $new_tokens = $client->getAccessToken();
            if (isset($new_tokens['access_token'])) {
                self::save_tokens($tutor_id, $new_tokens['access_token'], $refresh_token, $new_tokens['expires_in']);
                return $new_tokens['access_token'];
            }
        } catch (\Exception $e) {
            global $wpdb;
            $wpdb->delete($wpdb->prefix.'tutores_tokens',['tutor_id'=>$tutor_id]);
        }
        return false;
    }

    public static function handle_oauth() {
        if (isset($_GET['action']) && $_GET['action'] == 'tb_auth_google') {
            $tutor_id = isset($_GET['tutor_id']) ? intval($_GET['tutor_id']) : 0;
            $redirect = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : '';
            $client = self::get_client();
            $state = $tutor_id ? 'google_auth_' . $tutor_id : 'google_auth_email';
            if ($redirect) {
                $state .= '|' . urlencode($redirect);
            }
            $client->setState($state);
            wp_redirect($client->createAuthUrl());
            exit;
        }

        if (isset($_GET['code']) && isset($_GET['state'])) {
            $state_raw = $_GET['state'];
            $client = self::get_client();
            $parts = explode('|', $state_raw, 2);
            $state = $parts[0];
            $redirect_param = isset($parts[1]) ? wp_validate_redirect(urldecode($parts[1]), false) : false;
            try {
                $client->authenticate($_GET['code']);
                $tokens = $client->getAccessToken();
                if (!isset($tokens['access_token'])) {
                    wp_die('No se pudo obtener el token de acceso de Google.');
                }

                $status = 'success';
                if ($state === 'google_auth_email') {
                    $oauth2 = new \Google_Service_Oauth2($client);
                    $email = $oauth2->userinfo->get()->getEmail();
                    global $wpdb;
                    $tutor_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}tutores WHERE email=%s", $email));
                    if ($tutor_id) {
                        self::save_tokens($tutor_id, $tokens['access_token'], $tokens['refresh_token'] ?? null, $tokens['expires_in']);
                    } else {
                        $status = 'not_found';
                    }
                } elseif (strpos($state, 'google_auth_') === 0) {
                    $tutor_id = intval(str_replace('google_auth_', '', $state));
                    if (!$tutor_id) { wp_die('ID de tutor no válido en el estado de Google.'); }
                    self::save_tokens($tutor_id, $tokens['access_token'], $tokens['refresh_token'] ?? null, $tokens['expires_in']);
                } else {
                    return;
                }

                if ($redirect_param) {
                    $final_redirect = add_query_arg('google_auth', $status, $redirect_param);
                    wp_safe_redirect($final_redirect);
                } else {
                    if ($status === 'success') {
                        $final_redirect = is_admin()
                            ? admin_url('admin.php?page=tb-tutores&message=google_auth_success')
                            : home_url('?google_auth=success');
                    } else {
                        $final_redirect = is_admin()
                            ? admin_url('admin.php?page=tb-tutores&message=google_auth_not_found')
                            : home_url('?google_auth=not_found');
                    }
                    wp_redirect($final_redirect);
                }
                exit;
            } catch (\Exception $e) {
                wp_die('Error de autenticación de Google: ' . $e->getMessage());
            }
        }
    }
}

add_action('admin_init', [GoogleClient::class, 'handle_oauth']);
add_action('init', [GoogleClient::class, 'handle_oauth']);

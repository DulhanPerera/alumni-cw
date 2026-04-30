<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
   Name - Dulhan Perera
   IIT ID - 20210165
   UoW ID - w1912842

// API key management endpoints for secured integrations.
*/

/**
 * @property CI_Session $session
 * @property CI_Input $input
 * @property Api_key_model $Api_key_model
 * @property User_model $User_model
 */
class Api_keys extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        header("Access-Control-Allow-Origin: http://127.0.0.1:5500");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Content-Type: application/json");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
    }

        // API-key management needs the key model and session state.
        $this->load->model('Api_key_model');
        $this->load->library(['session']);
    }

    public function index()
    {
        // List endpoints stay read-only.
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        // Require a valid session before showing owned keys.
        $this->enforce_auth();
        $user_id = $this->require_login();

        $keys = $this->Api_key_model->get_keys_by_user($user_id);

        return $this->json_response([
            'status' => true,
            'message' => 'API keys fetched successfully.',
            'data' => [
                'api_keys' => $keys
            ]
        ]);
    }

    public function create()
    {
        // Key creation is a state-changing POST action.
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->method_not_allowed();
        }

        // Reuse the shared session timeout guard.
        $this->enforce_auth();
        $user_id = $this->require_login();
        $data = $this->get_json_input();

        $key_name = trim((string) ($data['key_name'] ?? ''));
        $scope = trim((string) ($data['scope'] ?? 'public'));
        $expires_at = trim((string) ($data['expires_at'] ?? ''));

        $errors = [];

        // Require a user-friendly name for the key.
        if ($key_name === '') {
            $errors['key_name'] = 'Key name is required.';
        }

        // Scope must be present even if the UI uses a default.
        if ($scope === '') {
            $errors['scope'] = 'Scope is required.';
        }

        if (!empty($errors)) {
            return $this->validation_error($errors);
        }

        $plain_key = 'ak_' . bin2hex(random_bytes(24));
        $key_hash = hash('sha256', $plain_key);
        $key_preview = substr($plain_key, 0, 10) . '...';

        // Store only the hashed key value in the database.
        $key_id = $this->Api_key_model->create_key([
            'created_by' => $user_id,
            'key_name' => $key_name,
            'key_preview' => $key_preview,
            'api_key_hash' => $key_hash,
            'scope' => $scope,
            'expires_at' => $expires_at !== '' ? $expires_at : null,
            'is_revoked' => 0
        ]);

        return $this->json_response([
            'status' => true,
            'message' => 'API key created successfully. Copy it now; it will not be shown again.',
            'data' => [
                'id' => $key_id,
                'key_name' => $key_name,
                'scope' => $scope,
                'key_preview' => $key_preview,
                'api_key' => $plain_key
            ]
        ], 201);
    }

    public function revoke($key_id)
    {
        // Revocation is also a POST action because it mutates state.
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->method_not_allowed();
        }

        // Enforce ownership before revoking anything.
        $this->enforce_auth();
        $user_id = $this->require_login();

        $key = $this->Api_key_model->get_key_by_id_and_user((int) $key_id, $user_id);

        if (!$key) {
            return $this->json_response([
                'status' => false,
                'message' => 'API key not found.'
            ], 404);
        }

        // Mark the key revoked instead of deleting it so audits remain intact.
        $this->Api_key_model->revoke_key((int) $key_id, $user_id);

        return $this->json_response([
            'status' => true,
            'message' => 'API key revoked successfully.'
        ]);
    }

    public function usage_logs()
    {
        // Usage history is read-only and session-protected.
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        // Keep the history limited to the authenticated owner.
        $this->enforce_auth();
        $user_id = $this->require_login();

        $logs = $this->Api_key_model->get_usage_logs_for_user($user_id);

        return $this->json_response([
            'status' => true,
            'message' => 'API key usage logs fetched successfully.',
            'data' => [
                'usage_logs' => $logs
            ]
        ]);
    }

    private function enforce_auth()
    {
        // Reuse the same expiration behavior as the other protected controllers.
        $last_activity = (int) $this->session->userdata('last_activity');

        if ($last_activity > 0 && (time() - $last_activity) > 1800) {
            $login_log_id = (int) $this->session->userdata('login_log_id');

            if ($login_log_id > 0) {
                // Record the logout before clearing the session.
                $this->load->model('User_model');
                $this->User_model->mark_logout_log($login_log_id);
            }

            $this->session->sess_destroy();
            $this->unauthorized('Session expired.');
            exit;
        }

        $this->session->set_userdata('last_activity', time());
    }
}
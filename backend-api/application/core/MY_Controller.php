<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base application controller helpers.
 *
 * @property CI_Output  $output
 * @property CI_Input   $input
 * @property CI_Session $session
 * @property Api_key_model $Api_key_model
 */
class MY_Controller extends CI_Controller
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
    }

    protected function json_response(array $payload, int $status_code = 200)
    {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($status_code)
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function get_json_input(): array
    {
        $raw = $this->input->raw_input_stream;

        if (!$raw) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function method_not_allowed()
    {
        return $this->json_response([
            'status' => false,
            'message' => 'Method not allowed'
        ], 405);
    }

    protected function unauthorized(string $message = 'Unauthorized')
    {
        return $this->json_response([
            'status' => false,
            'message' => $message
        ], 401);
    }

    protected function validation_error(array $errors)
    {
        return $this->json_response([
            'status' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ], 422);
    }

    protected function require_login(): int
    {
        $user_id = (int) $this->session->userdata('user_id');

        if ($user_id <= 0) {
            $this->unauthorized('Login required');
            exit;
        }

        return $user_id;
    }

    protected function get_bearer_token(): string
    {
        $headers = $this->input->request_headers();
        $authorization = '';

        if (isset($headers['Authorization'])) {
            $authorization = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authorization = $headers['authorization'];
        } elseif ($this->input->server('HTTP_AUTHORIZATION')) {
            $authorization = $this->input->server('HTTP_AUTHORIZATION');
        } elseif ($this->input->server('REDIRECT_HTTP_AUTHORIZATION')) {
            $authorization = $this->input->server('REDIRECT_HTTP_AUTHORIZATION');
        }

        if (!preg_match('/Bearer\s+(.+)/i', $authorization, $matches)) {
            return '';
        }

        return trim($matches[1]);
    }

    protected function require_api_scope(string $required_scope)
    {
        $this->load->model('Api_key_model');

        $plain_key = $this->get_bearer_token();

        if ($plain_key === '') {
            $this->json_response([
                'status' => false,
                'message' => 'Missing API bearer token.'
            ], 401);
            exit;
        }

        $key_hash = hash('sha256', $plain_key);

        $api_key = $this->Api_key_model->find_active_key_by_hash($key_hash);

        if (!$api_key) {
            $this->json_response([
                'status' => false,
                'message' => 'Invalid, expired, or revoked API key.'
            ], 401);
            exit;
        }

        $scopes = array_map('trim', explode(',', $api_key['scope']));

        if (!in_array($required_scope, $scopes)) {
            $this->json_response([
                'status' => false,
                'message' => 'This API key does not have permission: ' . $required_scope
            ], 403);
            exit;
        }

        $this->Api_key_model->update_last_used((int) $api_key['id']);

        $this->Api_key_model->log_usage([
            'api_key_id' => (int) $api_key['id'],
            'endpoint' => uri_string(),
            'method' => $this->input->method(TRUE),
            'ip_address' => $this->input->ip_address()
        ]);

        return $api_key;
    }
}
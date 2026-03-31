<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base application controller helpers.
 *
 * CodeIgniter injects these properties via the superobject at runtime.
 * The annotations keep static analysis aware of them.
 *
 * @property CI_Output  $output
 * @property CI_Input   $input
 * @property CI_Session $session
 * @property Api_key_model $Api_key_model
 */
class MY_Controller extends CI_Controller
{
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

    protected function require_api_key($required_scope = 'public')
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $auth_header = '';

        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $auth_header = $headers['authorization'];
        } else {
            $auth_header = (string) $this->input->server('HTTP_AUTHORIZATION');
        }

        if (!preg_match('/Bearer\s+(.+)/i', $auth_header, $matches)) {
            $this->json_response([
                'status' => false,
                'message' => 'Missing or invalid bearer token.'
            ], 401);
            exit;
        }

        $plain_key = trim($matches[1]);
        $key_hash = hash('sha256', $plain_key);

        $this->load->model('Api_key_model');
        $key = $this->Api_key_model->find_active_key_by_hash($key_hash);

        if (!$key) {
            $this->json_response([
                'status' => false,
                'message' => 'Invalid, expired, or revoked API key.'
            ], 401);
            exit;
        }

        if ($required_scope !== '' && $key['scope'] !== $required_scope) {
            $this->json_response([
                'status' => false,
                'message' => 'API key does not have the required scope.'
            ], 403);
            exit;
        }

        $this->Api_key_model->update_last_used((int) $key['id']);
        $this->Api_key_model->log_usage([
            'api_key_id' => (int) $key['id'],
            'endpoint' => uri_string(),
            'method' => $this->input->method(TRUE),
            'ip_address' => $this->input->ip_address()
        ]);

        return $key;
    }
}
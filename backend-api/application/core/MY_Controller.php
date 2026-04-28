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
        // Standardize JSON responses so API controllers stay consistent.
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($status_code)
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function get_json_input(): array
    {
        // Decode the request body once and return an empty array on invalid input.
        $raw = $this->input->raw_input_stream;

        if (!$raw) {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function method_not_allowed()
    {
        // Use one shared payload for method mismatch errors.
        return $this->json_response([
            'status' => false,
            'message' => 'Method not allowed'
        ], 405);
    }

    protected function unauthorized(string $message = 'Unauthorized')
    {
        // Emit a uniform 401 response for auth failures.
        return $this->json_response([
            'status' => false,
            'message' => $message
        ], 401);
    }

    protected function validation_error(array $errors)
    {
        // Keep validation failures structured for frontend form handling.
        return $this->json_response([
            'status' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ], 422);
    }

    protected function require_login(): int
    {
        // Ensure the session holds a real user ID before continuing.
        $user_id = (int) $this->session->userdata('user_id');

        if ($user_id <= 0) {
            $this->unauthorized('Login required');
            exit;
        }

        return $user_id;
    }

    protected function require_api_key($required_scope = 'public')
    {
        // Resolve the bearer token from whichever header variant the server exposes.
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
            // Reject requests without a usable bearer token.
            $this->json_response([
                'status' => false,
                'message' => 'Missing or invalid bearer token.'
            ], 401);
            exit;
        }

        $plain_key = trim($matches[1]);
        $key_hash = hash('sha256', $plain_key);

        // Look up the hashed key so the plaintext value never touches storage.
        $this->load->model('Api_key_model');
        $key = $this->Api_key_model->find_active_key_by_hash($key_hash);

        if (!$key) {
            // Treat revoked, expired, and unknown keys the same way.
            $this->json_response([
                'status' => false,
                'message' => 'Invalid, expired, or revoked API key.'
            ], 401);
            exit;
        }

        if ($required_scope !== '' && $key['scope'] !== $required_scope) {
            // Fail closed when the key scope does not match the endpoint.
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

    protected function require_api_scope($required_scope)
    {
        $this->load->model('Api_key_model');

        $headers = $this->input->request_headers();
        $authorization = '';

        if (isset($headers['Authorization'])) {
            $authorization = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authorization = $headers['authorization'];
        }

        if (empty($authorization) || strpos($authorization, 'Bearer ') !== 0) {
            http_response_code(401);
            echo json_encode([
                'status' => false,
                'message' => 'Missing API bearer token.'
            ]);
            exit;
        }

        $plain_key = trim(str_replace('Bearer ', '', $authorization));
        $key_hash = hash('sha256', $plain_key);

        $api_key = $this->Api_key_model->find_active_key_by_hash($key_hash);

        if (!$api_key) {
            http_response_code(401);
            echo json_encode([
                'status' => false,
                'message' => 'Invalid or expired API key.'
            ]);
            exit;
        }

        $scopes = array_map('trim', explode(',', $api_key['scope']));

        if (!in_array($required_scope, $scopes)) {
            http_response_code(403);
            echo json_encode([
                'status' => false,
                'message' => 'This API key does not have permission: ' . $required_scope
            ]);
            exit;
        }

        $this->Api_key_model->update_last_used($api_key['id']);

        $this->Api_key_model->log_usage([
            'api_key_id' => $api_key['id'],
            'endpoint' => uri_string(),
            'method' => $this->input->method(TRUE),
            'ip_address' => $this->input->ip_address()
        ]);

        return $api_key;
    }
}
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Session $session
 * @property CI_Input $input
 * @property CI_Form_validation $form_validation
 * @property User_model $User_model
 * @property Token_model $Token_model
 * @property CI_Email $email
 */
class Auth extends MY_Controller
{
    private string $allowed_email_domain = 'estminster.ac.uk';
    private int $session_timeout_seconds = 1800;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('User_model');
        $this->load->library(['session']);
        $this->load->helper(['url', 'security']);
    }

    public function register()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->method_not_allowed();
        }

        $data = $this->get_json_input();

        $full_name = trim((string) ($data['full_name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        $errors = [];

        if ($full_name === '' || mb_strlen($full_name) < 3) {
            $errors['full_name'] = 'Full name must be at least 3 characters.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required.';
        } else {
            $domain = substr(strrchr($email, '@'), 1);
            if ($domain !== $this->allowed_email_domain) {
                $errors['email'] = 'Only university email addresses are allowed.';
            }
        }

        if (!$this->is_strong_password($password)) {
            $errors['password'] = 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
        }

        if ($this->User_model->find_by_email($email)) {
            $errors['email'] = 'Email already registered.';
        }

        if (!empty($errors)) {
            return $this->validation_error($errors);
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        $user_id = $this->User_model->create_user([
            'full_name' => $full_name,
            'email' => $email,
            'password_hash' => $password_hash,
            'email_verified' => 0,
            'status' => 'active'
        ]);

        $plain_token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $plain_token);
        $expires_at = date('Y-m-d H:i:s', time() + 3600);

        $this->User_model->store_verification_token($user_id, $token_hash, $expires_at);

        $verify_url = site_url('api/auth/verify-email') . '?token=' . urlencode($plain_token);

        return $this->json_response([
            'status' => true,
            'message' => 'Registration successful. Please verify your email.',
            'data' => [
                'user_id' => $user_id,
                'verify_url' => $verify_url
            ]
        ], 201);
    }

    public function verify_email()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        $plain_token = trim((string) $this->input->get('token', true));

        if ($plain_token === '') {
            return $this->json_response([
                'status' => false,
                'message' => 'Verification token is required.'
            ], 400);
        }

        $token_hash = hash('sha256', $plain_token);
        $record = $this->User_model->find_valid_verification_token($token_hash);

        if (!$record) {
            return $this->json_response([
                'status' => false,
                'message' => 'Invalid or expired verification token.'
            ], 400);
        }

        $this->User_model->mark_email_verified((int) $record['user_id']);
        $this->User_model->mark_verification_token_used((int) $record['id']);

        return $this->json_response([
            'status' => true,
            'message' => 'Email verified successfully.'
        ]);
    }

    public function login()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->method_not_allowed();
        }

        $data = $this->get_json_input();

        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        $errors = [];

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        }

        if (!empty($errors)) {
            return $this->validation_error($errors);
        }

        $user = $this->User_model->find_by_email($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return $this->json_response([
                'status' => false,
                'message' => 'Invalid email or password.'
            ], 401);
        }

        if ((int) $user['email_verified'] !== 1) {
            return $this->json_response([
                'status' => false,
                'message' => 'Please verify your email before logging in.'
            ], 403);
        }

        if ($user['status'] !== 'active') {
            return $this->json_response([
                'status' => false,
                'message' => 'Account is not active.'
            ], 403);
        }

        $this->session->sess_regenerate(TRUE);

        $ip_address = $this->input->ip_address();
        $user_agent = substr((string) $this->input->user_agent(), 0, 255);
        $login_log_id = $this->User_model->create_login_log((int) $user['id'], $ip_address, $user_agent);

        $this->User_model->update_last_login((int) $user['id']);

        $this->session->set_userdata([
            'user_id' => (int) $user['id'],
            'user_email' => $user['email'],
            'user_name' => $user['full_name'],
            'login_log_id' => $login_log_id,
            'last_activity' => time()
        ]);

        return $this->json_response([
            'status' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => [
                    'id' => (int) $user['id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'email_verified' => (int) $user['email_verified'],
                    'status' => $user['status'],
                    'last_login_at' => date('Y-m-d H:i:s')
                ]
            ]
        ]);
    }

    public function logout()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->method_not_allowed();
        }

        $login_log_id = (int) $this->session->userdata('login_log_id');

        if ($login_log_id > 0) {
            $this->User_model->mark_logout_log($login_log_id);
        }

        $this->session->sess_destroy();

        return $this->json_response([
            'status' => true,
            'message' => 'Logout successful.'
        ]);
    }

    public function forgot_password()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->method_not_allowed();
        }

        $data = $this->get_json_input();
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->validation_error([
                'email' => 'Valid email is required.'
            ]);
        }

        $user = $this->User_model->find_by_email($email);

        if ($user) {
            $plain_token = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $plain_token);
            $expires_at = date('Y-m-d H:i:s', time() + 3600);

            $this->User_model->store_reset_token((int) $user['id'], $token_hash, $expires_at);

            $reset_token_for_testing = $plain_token;
        }

        return $this->json_response([
            'status' => true,
            'message' => 'If the email exists, a reset link has been generated.',
            'data' => isset($reset_token_for_testing)
                ? ['reset_token' => $reset_token_for_testing]
                : null
        ]);
    }

    public function reset_password()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->method_not_allowed();
        }

        $data = $this->get_json_input();

        $plain_token = trim((string) ($data['token'] ?? ''));
        $new_password = (string) ($data['new_password'] ?? '');

        $errors = [];

        if ($plain_token === '') {
            $errors['token'] = 'Reset token is required.';
        }

        if (!$this->is_strong_password($new_password)) {
            $errors['new_password'] = 'New password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
        }

        if (!empty($errors)) {
            return $this->validation_error($errors);
        }

        $token_hash = hash('sha256', $plain_token);
        $record = $this->User_model->find_valid_reset_token($token_hash);

        if (!$record) {
            return $this->json_response([
                'status' => false,
                'message' => 'Invalid or expired reset token.'
            ], 400);
        }

        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);

        $this->User_model->update_password((int) $record['user_id'], $password_hash);
        $this->User_model->mark_reset_token_used((int) $record['id']);

        return $this->json_response([
            'status' => true,
            'message' => 'Password has been reset successfully.'
        ]);
    }

    public function me()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        $this->enforce_session_timeout();

        $user_id = $this->require_login();
        $user = $this->User_model->find_by_id($user_id);

        if (!$user) {
            return $this->unauthorized('User not found.');
        }

        return $this->json_response([
            'status' => true,
            'message' => 'Authenticated user fetched successfully.',
            'data' => [
                'user' => [
                    'id' => (int) $user['id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'email_verified' => (int) $user['email_verified'],
                    'status' => $user['status'],
                    'last_login_at' => $user['last_login_at'],
                    'created_at' => $user['created_at'],
                    'updated_at' => $user['updated_at']
                ]
            ]
        ]);
    }

    private function is_strong_password(string $password): bool
    {
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password)
            && preg_match('/[\W_]/', $password);
    }

    private function enforce_session_timeout(): void
    {
        $user_id = (int) $this->session->userdata('user_id');

        if ($user_id <= 0) {
            return;
        }

        $last_activity = (int) $this->session->userdata('last_activity');

        if ($last_activity > 0 && (time() - $last_activity) > $this->session_timeout_seconds) {
            $login_log_id = (int) $this->session->userdata('login_log_id');

            if ($login_log_id > 0) {
                $this->User_model->mark_logout_log($login_log_id);
            }

            $this->session->sess_destroy();
            $this->unauthorized('Session expired.');
            exit;
        }

        $this->session->set_userdata('last_activity', time());
    }
}
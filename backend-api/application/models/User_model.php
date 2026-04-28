<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model
{
    private string $users_table = 'users';
    private string $verify_table = 'email_verification_tokens';
    private string $reset_table = 'password_reset_tokens';
    private string $login_logs_table = 'login_logs';

    public function find_by_email(string $email): ?array
    {
        // Look up a user by email and normalize the empty result to null.
        $row = $this->db
            ->get_where($this->users_table, ['email' => $email])
            ->row_array();

        return $row ?: null;
    }

    public function find_by_id(int $user_id): ?array
    {
        // Read the full user record for authenticated session lookups.
        $row = $this->db
            ->get_where($this->users_table, ['id' => $user_id])
            ->row_array();

        return $row ?: null;
    }

    public function create_user(array $data): int
    {
        // Insert a new account and return the generated primary key.
        $this->db->insert($this->users_table, $data);
        return (int) $this->db->insert_id();
    }

    public function mark_email_verified(int $user_id): bool
    {
        // Persist the verified flag after the token has been validated.
        return $this->db
            ->where('id', $user_id)
            ->update($this->users_table, [
                'email_verified' => 1
            ]);
    }

    public function update_password(int $user_id, string $password_hash): bool
    {
        // Store the new password hash only; raw passwords never reach the database.
        return $this->db
            ->where('id', $user_id)
            ->update($this->users_table, [
                'password_hash' => $password_hash
            ]);
    }

    public function update_last_login(int $user_id): bool
    {
        // Track the last successful login timestamp for account activity.
        return $this->db
            ->where('id', $user_id)
            ->update($this->users_table, [
                'last_login_at' => date('Y-m-d H:i:s')
            ]);
    }

    public function store_verification_token(int $user_id, string $token_hash, string $expires_at): bool
    {
        // Save the hashed email verification token and its expiry window.
        return $this->db->insert($this->verify_table, [
            'user_id' => $user_id,
            'token_hash' => $token_hash,
            'expires_at' => $expires_at
        ]);
    }

    public function store_reset_token(int $user_id, string $token_hash, string $expires_at): bool
    {
        // Save the hashed password reset token and its expiry window.
        return $this->db->insert($this->reset_table, [
            'user_id' => $user_id,
            'token_hash' => $token_hash,
            'expires_at' => $expires_at
        ]);
    }

    public function find_valid_verification_token(string $token_hash): ?array
    {
        // Only accept unused, unexpired tokens.
        $row = $this->db
            ->where('token_hash', $token_hash)
            ->where('used_at IS NULL', null, false)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->get($this->verify_table)
            ->row_array();

        return $row ?: null;
    }

    public function find_valid_reset_token(string $token_hash): ?array
    {
        // Password resets follow the same token rules as verification links.
        $row = $this->db
            ->where('token_hash', $token_hash)
            ->where('used_at IS NULL', null, false)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->get($this->reset_table)
            ->row_array();

        return $row ?: null;
    }

    public function mark_verification_token_used(int $token_id): bool
    {
        // Lock the token after successful verification to prevent reuse.
        return $this->db
            ->where('id', $token_id)
            ->update($this->verify_table, [
                'used_at' => date('Y-m-d H:i:s')
            ]);
    }

    public function mark_reset_token_used(int $token_id): bool
    {
        // Lock the token after a successful password reset.
        return $this->db
            ->where('id', $token_id)
            ->update($this->reset_table, [
                'used_at' => date('Y-m-d H:i:s')
            ]);
    }

    public function create_login_log(int $user_id, ?string $ip_address, ?string $user_agent): int
    {
        // Record each login so logout and audit trails can be completed later.
        $this->db->insert($this->login_logs_table, [
            'user_id' => $user_id,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent
        ]);

        return (int) $this->db->insert_id();
    }

    public function mark_logout_log(int $log_id): bool
    {
        // Mark the matching login row as closed when the session ends.
        return $this->db
            ->where('id', $log_id)
            ->update($this->login_logs_table, [
                'logged_out_at' => date('Y-m-d H:i:s')
            ]);
    }
}
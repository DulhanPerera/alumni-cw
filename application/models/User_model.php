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
        $row = $this->db
            ->get_where($this->users_table, ['email' => $email])
            ->row_array();

        return $row ?: null;
    }

    public function find_by_id(int $user_id): ?array
    {
        $row = $this->db
            ->get_where($this->users_table, ['id' => $user_id])
            ->row_array();

        return $row ?: null;
    }

    public function create_user(array $data): int
    {
        $this->db->insert($this->users_table, $data);
        return (int) $this->db->insert_id();
    }

    public function mark_email_verified(int $user_id): bool
    {
        return $this->db
            ->where('id', $user_id)
            ->update($this->users_table, [
                'email_verified' => 1
            ]);
    }

    public function update_password(int $user_id, string $password_hash): bool
    {
        return $this->db
            ->where('id', $user_id)
            ->update($this->users_table, [
                'password_hash' => $password_hash
            ]);
    }

    public function update_last_login(int $user_id): bool
    {
        return $this->db
            ->where('id', $user_id)
            ->update($this->users_table, [
                'last_login_at' => date('Y-m-d H:i:s')
            ]);
    }

    public function store_verification_token(int $user_id, string $token_hash, string $expires_at): bool
    {
        return $this->db->insert($this->verify_table, [
            'user_id' => $user_id,
            'token_hash' => $token_hash,
            'expires_at' => $expires_at
        ]);
    }

    public function store_reset_token(int $user_id, string $token_hash, string $expires_at): bool
    {
        return $this->db->insert($this->reset_table, [
            'user_id' => $user_id,
            'token_hash' => $token_hash,
            'expires_at' => $expires_at
        ]);
    }

    public function find_valid_verification_token(string $token_hash): ?array
    {
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
        return $this->db
            ->where('id', $token_id)
            ->update($this->verify_table, [
                'used_at' => date('Y-m-d H:i:s')
            ]);
    }

    public function mark_reset_token_used(int $token_id): bool
    {
        return $this->db
            ->where('id', $token_id)
            ->update($this->reset_table, [
                'used_at' => date('Y-m-d H:i:s')
            ]);
    }

    public function create_login_log(int $user_id, ?string $ip_address, ?string $user_agent): int
    {
        $this->db->insert($this->login_logs_table, [
            'user_id' => $user_id,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent
        ]);

        return (int) $this->db->insert_id();
    }

    public function mark_logout_log(int $log_id): bool
    {
        return $this->db
            ->where('id', $log_id)
            ->update($this->login_logs_table, [
                'logged_out_at' => date('Y-m-d H:i:s')
            ]);
    }
}
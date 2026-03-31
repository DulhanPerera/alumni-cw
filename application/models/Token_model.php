<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Token_model extends CI_Model
{
    public function create_verification_token($data)
    {
        return $this->db->insert('email_verification_tokens', $data);
    }

    public function get_valid_verification_token($token_hash)
    {
        return $this->db
            ->where('token_hash', $token_hash)
            ->where('used_at IS NULL', null, false)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->get('email_verification_tokens')
            ->row();
    }

    public function mark_verification_token_used($id)
    {
        return $this->db
            ->where('id', $id)
            ->update('email_verification_tokens', [
                'used_at' => date('Y-m-d H:i:s')
            ]);
    }

    public function create_reset_token($data)
    {
        return $this->db->insert('password_reset_tokens', $data);
    }

    public function get_valid_reset_token($token_hash)
    {
        return $this->db
            ->where('token_hash', $token_hash)
            ->where('used_at IS NULL', null, false)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->get('password_reset_tokens')
            ->row();
    }

    public function mark_reset_token_used($id)
    {
        return $this->db
            ->where('id', $id)
            ->update('password_reset_tokens', [
                'used_at' => date('Y-m-d H:i:s')
            ]);
    }
}
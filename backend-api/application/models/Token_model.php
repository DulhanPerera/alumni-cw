<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
   Name - Dulhan Perera
   IIT ID - 20210165
   UoW ID - w1912842
*/

// Token lifecycle queries for password and email workflows.

class Token_model extends CI_Model
{
    public function create_verification_token($data)
    {
        // Create a verification token row from a prebuilt payload.
        return $this->db->insert('email_verification_tokens', $data);
    }

    public function get_valid_verification_token($token_hash)
    {
        // Return the first matching token that is unused and still valid.
        return $this->db
            ->where('token_hash', $token_hash)
            ->where('used_at IS NULL', null, false)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->get('email_verification_tokens')
            ->row();
    }

    public function mark_verification_token_used($id)
    {
        // Store the usage timestamp so the token cannot be replayed.
        return $this->db
            ->where('id', $id)
            ->update('email_verification_tokens', [
                'used_at' => date('Y-m-d H:i:s')
            ]);
    }

    public function create_reset_token($data)
    {
        // Persist a reset token payload for later validation.
        return $this->db->insert('password_reset_tokens', $data);
    }

    public function get_valid_reset_token($token_hash)
    {
        // Return a reset token only while it is unused and unexpired.
        return $this->db
            ->where('token_hash', $token_hash)
            ->where('used_at IS NULL', null, false)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->get('password_reset_tokens')
            ->row();
    }

    public function mark_reset_token_used($id)
    {
        // Mark the reset token as consumed immediately after use.
        return $this->db
            ->where('id', $id)
            ->update('password_reset_tokens', [
                'used_at' => date('Y-m-d H:i:s')
            ]);
    }
}
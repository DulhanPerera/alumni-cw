<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_key_model extends CI_Model
{
    private $api_keys_table = 'api_keys';
    private $usage_logs_table = 'api_key_usage_logs';

    public function create_key($data)
    {
        $this->db->insert($this->api_keys_table, $data);
        return (int) $this->db->insert_id();
    }

    public function get_keys_by_user($user_id)
    {
        return $this->db
            ->where('created_by', $user_id)
            ->order_by('created_at', 'DESC')
            ->get($this->api_keys_table)
            ->result_array();
    }

    public function get_key_by_id_and_user($key_id, $user_id)
    {
        $row = $this->db
            ->get_where($this->api_keys_table, [
                'id' => $key_id,
                'created_by' => $user_id
            ])
            ->row_array();

        return $row ?: null;
    }

    public function revoke_key($key_id, $user_id)
    {
        return $this->db
            ->where('id', $key_id)
            ->where('created_by', $user_id)
            ->update($this->api_keys_table, [
                'is_revoked' => 1
            ]);
    }

    public function find_active_key_by_hash($api_key_hash)
    {
        $row = $this->db
            ->where('api_key_hash', $api_key_hash)
            ->where('is_revoked', 0)
            ->group_start()
                ->where('expires_at IS NULL', null, false)
                ->or_where('expires_at >=', date('Y-m-d H:i:s'))
            ->group_end()
            ->get($this->api_keys_table)
            ->row_array();

        return $row ?: null;
    }

    public function update_last_used($key_id)
    {
        return $this->db
            ->where('id', $key_id)
            ->update($this->api_keys_table, [
                'last_used_at' => date('Y-m-d H:i:s')
            ]);
    }

    public function log_usage($data)
    {
        return $this->db->insert($this->usage_logs_table, $data);
    }

    public function get_usage_logs_for_user($user_id)
    {
        return $this->db
            ->select('l.*, k.key_name, k.key_preview')
            ->from($this->usage_logs_table . ' l')
            ->join($this->api_keys_table . ' k', 'k.id = l.api_key_id')
            ->where('k.created_by', $user_id)
            ->order_by('l.used_at', 'DESC')
            ->get()
            ->result_array();
    }
}
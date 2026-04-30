<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
   Name - Dulhan Perera
   IIT ID - 20210165
   UoW ID - w1912842
*/

// API key validation and usage tracking queries.

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
            ->select('id, key_name, key_preview, scope, last_used_at, expires_at, is_revoked, created_at')
            ->where('created_by', (int) $user_id)
            ->order_by('created_at', 'DESC')
            ->get($this->api_keys_table)
            ->result_array();
    }

    public function get_key_by_id_and_user($key_id, $user_id)
    {
        $row = $this->db
            ->where('id', (int) $key_id)
            ->where('created_by', (int) $user_id)
            ->get($this->api_keys_table)
            ->row_array();

        return $row ?: null;
    }

    public function revoke_key($key_id, $user_id)
    {
        return $this->db
            ->where('id', (int) $key_id)
            ->where('created_by', (int) $user_id)
            ->update($this->api_keys_table, [
                'is_revoked' => 1
            ]);
    }

    public function find_active_key_by_hash($key_hash)
    {
        $row = $this->db
            ->where('api_key_hash', $key_hash)
            ->where('is_revoked', 0)
            ->group_start()
                ->where('expires_at IS NULL', null, false)
                ->or_where('expires_at >=', date('Y-m-d H:i:s'))
            ->group_end()
            ->get($this->api_keys_table)
            ->row_array();

        return $row ?: null;
    }

    public function update_last_used($api_key_id)
    {
        return $this->db
            ->where('id', (int) $api_key_id)
            ->update($this->api_keys_table, [
                'last_used_at' => date('Y-m-d H:i:s')
            ]);
    }

    public function log_usage($data)
    {
        $payload = [
            'api_key_id' => (int) $data['api_key_id'],
            'endpoint' => $data['endpoint'] ?? '',
            'method' => $data['method'] ?? '',
            'ip_address' => $data['ip_address'] ?? null
        ];

        return $this->db->insert($this->usage_logs_table, $payload);
    }

    public function get_usage_logs_for_user($user_id)
    {
        return $this->db
            ->select('
                l.id,
                l.api_key_id,
                l.endpoint,
                l.method,
                l.ip_address,
                l.used_at,
                k.key_name,
                k.key_preview,
                k.scope
            ')
            ->from($this->usage_logs_table . ' l')
            ->join($this->api_keys_table . ' k', 'k.id = l.api_key_id')
            ->where('k.created_by', (int) $user_id)
            ->order_by('l.used_at', 'DESC')
            ->get()
            ->result_array();
    }

    public function get_usage_summary_for_user($user_id)
    {
        return $this->db
            ->select('
                k.id AS api_key_id,
                k.key_name,
                k.key_preview,
                k.scope,
                COUNT(l.id) AS total_requests,
                MAX(l.used_at) AS last_used_at
            ')
            ->from($this->api_keys_table . ' k')
            ->join($this->usage_logs_table . ' l', 'l.api_key_id = k.id', 'left')
            ->where('k.created_by', (int) $user_id)
            ->group_by('k.id, k.key_name, k.key_preview, k.scope')
            ->order_by('total_requests', 'DESC')
            ->get()
            ->result_array();
    }

    public function get_endpoint_usage_for_user($user_id)
    {
        return $this->db
            ->select('
                l.endpoint,
                l.method,
                COUNT(l.id) AS total_requests
            ')
            ->from($this->usage_logs_table . ' l')
            ->join($this->api_keys_table . ' k', 'k.id = l.api_key_id')
            ->where('k.created_by', (int) $user_id)
            ->group_by('l.endpoint, l.method')
            ->order_by('total_requests', 'DESC')
            ->get()
            ->result_array();
    }
}
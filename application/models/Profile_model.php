<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile_model extends CI_Model
{
    private $profiles_table = 'profiles';
    private $degrees_table = 'degrees';
    private $certifications_table = 'certifications';
    private $licenses_table = 'licenses';
    private $short_courses_table = 'short_courses';
    private $employment_table = 'employment_history';

    public function get_profile_by_user_id($user_id)
    {
        $profile = $this->db
            ->get_where($this->profiles_table, ['user_id' => $user_id])
            ->row_array();

        if (!$profile) {
            return null;
        }

        $profile['degrees'] = $this->db
            ->order_by('id', 'DESC')
            ->get_where($this->degrees_table, ['profile_id' => $profile['id']])
            ->result_array();

        $profile['certifications'] = $this->db
            ->order_by('id', 'DESC')
            ->get_where($this->certifications_table, ['profile_id' => $profile['id']])
            ->result_array();

        $profile['licenses'] = $this->db
            ->order_by('id', 'DESC')
            ->get_where($this->licenses_table, ['profile_id' => $profile['id']])
            ->result_array();

        $profile['short_courses'] = $this->db
            ->order_by('id', 'DESC')
            ->get_where($this->short_courses_table, ['profile_id' => $profile['id']])
            ->result_array();

        $profile['employment_history'] = $this->db
            ->order_by('start_date', 'DESC')
            ->get_where($this->employment_table, ['profile_id' => $profile['id']])
            ->result_array();

        return $profile;
    }

    public function create_profile($data)
    {
        $this->db->insert($this->profiles_table, $data);
        return (int) $this->db->insert_id();
    }

    public function update_profile_by_user_id($user_id, $data)
    {
        return $this->db
            ->where('user_id', $user_id)
            ->update($this->profiles_table, $data);
    }

    public function get_profile_id_by_user_id($user_id)
    {
        $row = $this->db
            ->select('id')
            ->get_where($this->profiles_table, ['user_id' => $user_id])
            ->row_array();

        return $row ? (int) $row['id'] : null;
    }

    public function add_degree($data)
    {
        $this->db->insert($this->degrees_table, $data);
        return (int) $this->db->insert_id();
    }

    public function update_degree($id, $profile_id, $data)
    {
        return $this->db
            ->where('id', $id)
            ->where('profile_id', $profile_id)
            ->update($this->degrees_table, $data);
    }

    public function delete_degree($id, $profile_id)
    {
        return $this->db
            ->where('id', $id)
            ->where('profile_id', $profile_id)
            ->delete($this->degrees_table);
    }

    public function add_certification($data)
    {
        $this->db->insert($this->certifications_table, $data);
        return (int) $this->db->insert_id();
    }

    public function update_certification($id, $profile_id, $data)
    {
        return $this->db
            ->where('id', $id)
            ->where('profile_id', $profile_id)
            ->update($this->certifications_table, $data);
    }

    public function delete_certification($id, $profile_id)
    {
        return $this->db
            ->where('id', $id)
            ->where('profile_id', $profile_id)
            ->delete($this->certifications_table);
    }

    public function add_license($data)
    {
        $this->db->insert($this->licenses_table, $data);
        return (int) $this->db->insert_id();
    }

    public function update_license($id, $profile_id, $data)
    {
        return $this->db
            ->where('id', $id)
            ->where('profile_id', $profile_id)
            ->update($this->licenses_table, $data);
    }

    public function delete_license($id, $profile_id)
    {
        return $this->db
            ->where('id', $id)
            ->where('profile_id', $profile_id)
            ->delete($this->licenses_table);
    }

    public function add_short_course($data)
    {
        $this->db->insert($this->short_courses_table, $data);
        return (int) $this->db->insert_id();
    }

    public function update_short_course($id, $profile_id, $data)
    {
        return $this->db
            ->where('id', $id)
            ->where('profile_id', $profile_id)
            ->update($this->short_courses_table, $data);
    }

    public function delete_short_course($id, $profile_id)
    {
        return $this->db
            ->where('id', $id)
            ->where('profile_id', $profile_id)
            ->delete($this->short_courses_table);
    }

    public function add_employment($data)
    {
        $this->db->insert($this->employment_table, $data);
        return (int) $this->db->insert_id();
    }

    public function update_employment($id, $profile_id, $data)
    {
        return $this->db
            ->where('id', $id)
            ->where('profile_id', $profile_id)
            ->update($this->employment_table, $data);
    }

    public function delete_employment($id, $profile_id)
    {
        return $this->db
            ->where('id', $id)
            ->where('profile_id', $profile_id)
            ->delete($this->employment_table);
    }

    public function clear_current_employment($profile_id, $exclude_id = null)
    {
        $this->db->where('profile_id', $profile_id);

        if ($exclude_id !== null) {
            $this->db->where('id !=', $exclude_id);
        }

        return $this->db->update($this->employment_table, ['is_current' => 0]);
    }
}
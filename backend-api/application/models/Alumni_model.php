<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Alumni_model extends CI_Model
{
    private function apply_filters($filters)
    {
        if (!empty($filters['programme'])) {
            $this->db->like('degrees.degree_name', $filters['programme']);
        }

        if (!empty($filters['graduation_year'])) {
            $year = (int) $filters['graduation_year'];
            $this->db->where('YEAR(degrees.completion_date) = ' . $year, null, false);
        }

        if (!empty($filters['industry_sector'])) {
            $this->db->like('employment_history.description', $filters['industry_sector']);
        }
    }

    public function get_alumni($filters = [])
    {
        $this->db->select('
            users.id,
            users.full_name,
            users.email,
            profiles.headline,
            profiles.current_job_title,
            profiles.current_company,
            degrees.degree_name AS programme,
            YEAR(degrees.completion_date) AS graduation_year,
            employment_history.job_title,
            employment_history.company_name,
            employment_history.description AS industry_sector
        ', false);

        $this->db->from('users');
        $this->db->join('profiles', 'profiles.user_id = users.id', 'left');
        $this->db->join('degrees', 'degrees.profile_id = profiles.id', 'left');
        $this->db->join('employment_history', 'employment_history.profile_id = profiles.id', 'left');

        $this->apply_filters($filters);

        $this->db->order_by('users.full_name', 'ASC');

        return $this->db->get()->result_array();
    }

    public function get_alumnus_by_id($id)
    {
        $this->db->select('
            users.id,
            users.full_name,
            users.email,
            profiles.headline,
            profiles.biography,
            profiles.linkedin_url,
            profiles.profile_image,
            profiles.current_job_title,
            profiles.current_company,
            degrees.degree_name AS programme,
            degrees.institution_name,
            degrees.degree_url,
            degrees.completion_date,
            YEAR(degrees.completion_date) AS graduation_year,
            employment_history.job_title,
            employment_history.company_name,
            employment_history.start_date,
            employment_history.end_date,
            employment_history.is_current,
            employment_history.description AS industry_sector
        ', false);

        $this->db->from('users');
        $this->db->join('profiles', 'profiles.user_id = users.id', 'left');
        $this->db->join('degrees', 'degrees.profile_id = profiles.id', 'left');
        $this->db->join('employment_history', 'employment_history.profile_id = profiles.id', 'left');
        $this->db->where('users.id', (int) $id);

        return $this->db->get()->row_array();
    }
}
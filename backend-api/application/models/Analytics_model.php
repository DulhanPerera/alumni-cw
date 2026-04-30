<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
   Name - Dulhan Perera
   IIT ID - 20210165
   UoW ID - w1912842
*/

// Analytics queries used by the dashboard and reports.

class Analytics_model extends CI_Model
{
    private function apply_filters($filters)
    {
        if (!empty($filters['programme'])) {
            $this->db->like('degrees.degree_name', $filters['programme']);
        }

        if (!empty($filters['graduation_year'])) {
            $this->db->where('YEAR(degrees.completion_date) =', (int) $filters['graduation_year']);
        }

        if (!empty($filters['industry_sector'])) {
            $this->db->like('employment_history.description', $filters['industry_sector']);
        }
    }

    public function get_summary($filters = [])
    {
        $this->db->select('COUNT(DISTINCT users.id) AS total_alumni');
        $this->db->from('users');
        $this->db->join('profiles', 'profiles.user_id = users.id', 'left');
        $this->db->join('degrees', 'degrees.profile_id = profiles.id', 'left');
        $this->db->join('employment_history', 'employment_history.profile_id = profiles.id', 'left');
        $this->apply_filters($filters);
        $total_alumni_row = $this->db->get()->row_array();
        $total_alumni = (int) ($total_alumni_row['total_alumni'] ?? 0);

        $this->db->select('COUNT(DISTINCT degrees.degree_name) AS total_programmes');
        $this->db->from('users');
        $this->db->join('profiles', 'profiles.user_id = users.id', 'left');
        $this->db->join('degrees', 'degrees.profile_id = profiles.id', 'left');
        $this->db->join('employment_history', 'employment_history.profile_id = profiles.id', 'left');
        $this->apply_filters($filters);
        $this->db->where('degrees.degree_name IS NOT NULL', null, false);
        $total_programmes_row = $this->db->get()->row_array();
        $total_programmes = (int) ($total_programmes_row['total_programmes'] ?? 0);

        $this->db->select('COUNT(DISTINCT employment_history.description) AS total_sectors');
        $this->db->from('users');
        $this->db->join('profiles', 'profiles.user_id = users.id', 'left');
        $this->db->join('degrees', 'degrees.profile_id = profiles.id', 'left');
        $this->db->join('employment_history', 'employment_history.profile_id = profiles.id', 'left');
        $this->apply_filters($filters);
        $this->db->where('employment_history.description IS NOT NULL', null, false);
        $total_sectors_row = $this->db->get()->row_array();
        $total_sectors = (int) ($total_sectors_row['total_sectors'] ?? 0);

        $this->db->select('COUNT(DISTINCT certifications.id) AS total_certifications');
        $this->db->from('users');
        $this->db->join('profiles', 'profiles.user_id = users.id', 'left');
        $this->db->join('degrees', 'degrees.profile_id = profiles.id', 'left');
        $this->db->join('employment_history', 'employment_history.profile_id = profiles.id', 'left');
        $this->db->join('certifications', 'certifications.profile_id = profiles.id', 'left');
        $this->apply_filters($filters);
        $total_certifications_row = $this->db->get()->row_array();
        $total_certifications = (int) ($total_certifications_row['total_certifications'] ?? 0);

        return [
            'total_alumni' => $total_alumni,
            'total_programmes' => $total_programmes,
            'total_sectors' => $total_sectors,
            'total_certifications' => $total_certifications
        ];
    }

    public function get_alumni_by_programme($filters = [])
    {
        $this->db->select('degrees.degree_name AS programme, COUNT(DISTINCT users.id) AS total');
        $this->db->from('users');
        $this->db->join('profiles', 'profiles.user_id = users.id', 'left');
        $this->db->join('degrees', 'degrees.profile_id = profiles.id', 'left');
        $this->db->join('employment_history', 'employment_history.profile_id = profiles.id', 'left');

        $this->apply_filters($filters);

        $this->db->where('degrees.degree_name IS NOT NULL', null, false);
        $this->db->group_by('degrees.degree_name');
        $this->db->order_by('total', 'DESC');

        return $this->db->get()->result_array();
    }

    public function get_employment_by_sector($filters = [])
    {
        $this->db->select('employment_history.description AS industry_sector, COUNT(DISTINCT users.id) AS total');
        $this->db->from('users');
        $this->db->join('profiles', 'profiles.user_id = users.id', 'left');
        $this->db->join('degrees', 'degrees.profile_id = profiles.id', 'left');
        $this->db->join('employment_history', 'employment_history.profile_id = profiles.id', 'left');

        $this->apply_filters($filters);

        $this->db->where('employment_history.description IS NOT NULL', null, false);
        $this->db->group_by('employment_history.description');
        $this->db->order_by('total', 'DESC');

        return $this->db->get()->result_array();
    }

    public function get_top_job_titles($filters = [])
    {
        $this->db->select('employment_history.job_title, COUNT(DISTINCT users.id) AS total');
        $this->db->from('users');
        $this->db->join('profiles', 'profiles.user_id = users.id', 'left');
        $this->db->join('degrees', 'degrees.profile_id = profiles.id', 'left');
        $this->db->join('employment_history', 'employment_history.profile_id = profiles.id', 'left');

        $this->apply_filters($filters);

        $this->db->where('employment_history.job_title IS NOT NULL', null, false);
        $this->db->group_by('employment_history.job_title');
        $this->db->order_by('total', 'DESC');
        $this->db->limit(10);

        return $this->db->get()->result_array();
    }

    public function get_top_employers($filters = [])
    {
        $this->db->select('employment_history.company_name, COUNT(DISTINCT users.id) AS total');
        $this->db->from('users');
        $this->db->join('profiles', 'profiles.user_id = users.id', 'left');
        $this->db->join('degrees', 'degrees.profile_id = profiles.id', 'left');
        $this->db->join('employment_history', 'employment_history.profile_id = profiles.id', 'left');

        $this->apply_filters($filters);

        $this->db->where('employment_history.company_name IS NOT NULL', null, false);
        $this->db->group_by('employment_history.company_name');
        $this->db->order_by('total', 'DESC');
        $this->db->limit(10);

        return $this->db->get()->result_array();
    }

    public function get_certification_growth($filters = [])
    {
        $this->db->select('YEAR(certifications.completion_date) AS year, COUNT(DISTINCT certifications.id) AS total');
        $this->db->from('users');
        $this->db->join('profiles', 'profiles.user_id = users.id', 'left');
        $this->db->join('degrees', 'degrees.profile_id = profiles.id', 'left');
        $this->db->join('employment_history', 'employment_history.profile_id = profiles.id', 'left');
        $this->db->join('certifications', 'certifications.profile_id = profiles.id', 'left');

        $this->apply_filters($filters);

        $this->db->where('certifications.completion_date IS NOT NULL', null, false);
        $this->db->group_by('YEAR(certifications.completion_date)');
        $this->db->order_by('year', 'ASC');

        return $this->db->get()->result_array();
    }

    public function get_geographic_distribution($filters = [])
    {
        $this->db->select('profiles.current_company AS location, COUNT(DISTINCT users.id) AS total');
        $this->db->from('users');
        $this->db->join('profiles', 'profiles.user_id = users.id', 'left');
        $this->db->join('degrees', 'degrees.profile_id = profiles.id', 'left');
        $this->db->join('employment_history', 'employment_history.profile_id = profiles.id', 'left');

        $this->apply_filters($filters);

        $this->db->where('profiles.current_company IS NOT NULL', null, false);
        $this->db->group_by('profiles.current_company');
        $this->db->order_by('total', 'DESC');
        $this->db->limit(10);

        return $this->db->get()->result_array();
    }
}
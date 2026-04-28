<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Analytics_model extends CI_Model
{
    public function get_summary()
    {
        $total_alumni = $this->db->count_all('users');

        $total_programmes = $this->db
            ->select('degree_name')
            ->from('degrees')
            ->group_by('degree_name')
            ->get()
            ->num_rows();

        $total_sectors = $this->db
            ->select('description')
            ->from('employment_history')
            ->where('description IS NOT NULL', null, false)
            ->group_by('description')
            ->get()
            ->num_rows();

        $total_certifications = $this->db->count_all('certifications');

        return [
            'total_alumni' => $total_alumni,
            'total_programmes' => $total_programmes,
            'total_sectors' => $total_sectors,
            'total_certifications' => $total_certifications
        ];
    }

    public function get_alumni_by_programme()
    {
        return $this->db
            ->select('degree_name AS programme, COUNT(*) AS total')
            ->from('degrees')
            ->group_by('degree_name')
            ->order_by('total', 'DESC')
            ->get()
            ->result_array();
    }

    public function get_employment_by_sector()
    {
        return $this->db
            ->select('description AS industry_sector, COUNT(*) AS total')
            ->from('employment_history')
            ->where('description IS NOT NULL', null, false)
            ->group_by('description')
            ->order_by('total', 'DESC')
            ->get()
            ->result_array();
    }

    public function get_top_job_titles()
    {
        return $this->db
            ->select('job_title, COUNT(*) AS total')
            ->from('employment_history')
            ->group_by('job_title')
            ->order_by('total', 'DESC')
            ->limit(10)
            ->get()
            ->result_array();
    }

    public function get_top_employers()
    {
        return $this->db
            ->select('company_name, COUNT(*) AS total')
            ->from('employment_history')
            ->group_by('company_name')
            ->order_by('total', 'DESC')
            ->limit(10)
            ->get()
            ->result_array();
    }

    public function get_certification_growth()
    {
        return $this->db
            ->select('YEAR(completion_date) AS year, COUNT(*) AS total')
            ->from('certifications')
            ->where('completion_date IS NOT NULL', null, false)
            ->group_by('YEAR(completion_date)')
            ->order_by('year', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_geographic_distribution()
    {
        return $this->db
            ->select('current_company AS location, COUNT(*) AS total')
            ->from('profiles')
            ->where('current_company IS NOT NULL', null, false)
            ->group_by('current_company')
            ->order_by('total', 'DESC')
            ->limit(10)
            ->get()
            ->result_array();
    }
}
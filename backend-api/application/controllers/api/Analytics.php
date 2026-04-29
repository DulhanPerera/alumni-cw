<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Session $session
 * @property CI_Input $input
 * @property Analytics_model $Analytics_model
 */
class Analytics extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Analytics_model');

        $this->require_api_scope('read:analytics');
    }

    private function response($status, $message, $data = [])
    {
        return $this->json_response([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ]);
    }

    private function get_filters()
    {
        return [
            'programme' => trim((string) $this->input->get('programme', true)),
            'graduation_year' => trim((string) $this->input->get('graduation_year', true)),
            'industry_sector' => trim((string) $this->input->get('industry_sector', true))
        ];
    }

    public function summary()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        $filters = $this->get_filters();
        $data = $this->Analytics_model->get_summary($filters);

        return $this->response(true, 'Dashboard summary loaded successfully.', $data);
    }

    public function alumni_by_programme()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        $filters = $this->get_filters();
        $data = $this->Analytics_model->get_alumni_by_programme($filters);

        return $this->response(true, 'Alumni by programme loaded successfully.', $data);
    }

    public function employment_by_sector()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        $filters = $this->get_filters();
        $data = $this->Analytics_model->get_employment_by_sector($filters);

        return $this->response(true, 'Employment by sector loaded successfully.', $data);
    }

    public function top_job_titles()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        $filters = $this->get_filters();
        $data = $this->Analytics_model->get_top_job_titles($filters);

        return $this->response(true, 'Top job titles loaded successfully.', $data);
    }

    public function top_employers()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        $filters = $this->get_filters();
        $data = $this->Analytics_model->get_top_employers($filters);

        return $this->response(true, 'Top employers loaded successfully.', $data);
    }

    public function certification_growth()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        $filters = $this->get_filters();
        $data = $this->Analytics_model->get_certification_growth($filters);

        return $this->response(true, 'Certification growth loaded successfully.', $data);
    }

    public function geographic_distribution()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        $filters = $this->get_filters();
        $data = $this->Analytics_model->get_geographic_distribution($filters);

        return $this->response(true, 'Geographic distribution loaded successfully.', $data);
    }
}
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

        header("Access-Control-Allow-Origin: http://127.0.0.1:5500");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Content-Type: application/json");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $this->load->model('Analytics_model');

        $this->require_api_scope('read:analytics');
    }

    private function response($status, $message, $data = [])
    {
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ]);
    }

    public function summary()
    {
        $data = $this->Analytics_model->get_summary();

        echo json_encode([
            'status' => true,
            'message' => 'Dashboard summary loaded successfully.',
            'data' => $data
        ]);
    }

    public function alumni_by_programme()
    {
        $data = $this->Analytics_model->get_alumni_by_programme();
        $this->response(true, 'Alumni by programme loaded successfully.', $data);
    }

    public function employment_by_sector()
    {
        $data = $this->Analytics_model->get_employment_by_sector();
        $this->response(true, 'Employment by sector loaded successfully.', $data);
    }

    public function top_job_titles()
    {
        $data = $this->Analytics_model->get_top_job_titles();
        $this->response(true, 'Top job titles loaded successfully.', $data);
    }

    public function top_employers()
    {
        $data = $this->Analytics_model->get_top_employers();
        $this->response(true, 'Top employers loaded successfully.', $data);
    }

    public function certification_growth()
    {
        $data = $this->Analytics_model->get_certification_growth();
        $this->response(true, 'Certification growth loaded successfully.', $data);
    }

    public function geographic_distribution()
    {
        $data = $this->Analytics_model->get_geographic_distribution();
        $this->response(true, 'Geographic distribution loaded successfully.', $data);
    }
}
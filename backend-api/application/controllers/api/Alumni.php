<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Session $session
 * @property CI_Input $input
 * @property Alumni_model $Alumni_model
 */
class Alumni extends CI_Controller
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

        $this->load->model('Alumni_model');
    }

    private function response($status, $message, $data = [])
    {
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ]);
    }

    public function index()
    {
        $filters = [
            'programme' => $this->input->get('programme'),
            'graduation_year' => $this->input->get('graduation_year'),
            'industry_sector' => $this->input->get('industry_sector')
        ];

        $alumni = $this->Alumni_model->get_alumni($filters);

        $this->response(true, 'Alumni records loaded successfully.', $alumni);
    }

    public function show($id)
    {
        $alumnus = $this->Alumni_model->get_alumnus_by_id($id);

        if (!$alumnus) {
            $this->response(false, 'Alumnus not found.');
            return;
        }

        $this->response(true, 'Alumnus loaded successfully.', $alumnus);
    }
}
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
   Name - Dulhan Perera
   IIT ID - 20210165
   UoW ID - w1912842

// Alumni directory endpoints for profile browsing and updates.
*/

/**
 * @property CI_Session $session
 * @property CI_Input $input
 * @property Alumni_model $Alumni_model
 */

class Alumni extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Alumni_model');

        $this->require_api_scope('read:alumni');
    }

    private function get_filters()
    {
        return [
            'programme' => trim((string) $this->input->get('programme', true)),
            'graduation_year' => trim((string) $this->input->get('graduation_year', true)),
            'industry_sector' => trim((string) $this->input->get('industry_sector', true))
        ];
    }

    public function index()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        $filters = $this->get_filters();
        $alumni = $this->Alumni_model->get_alumni($filters);

        return $this->json_response([
            'status' => true,
            'message' => 'Alumni records loaded successfully.',
            'data' => $alumni
        ]);
    }

    public function show($id)
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        $alumnus = $this->Alumni_model->get_alumnus_by_id((int) $id);

        if (!$alumnus) {
            return $this->json_response([
                'status' => false,
                'message' => 'Alumnus not found.'
            ], 404);
        }

        return $this->json_response([
            'status' => true,
            'message' => 'Alumnus loaded successfully.',
            'data' => $alumnus
        ]);
    }
}
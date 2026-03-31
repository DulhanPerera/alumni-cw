<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Input $input
 * @property Bid_model $Bid_model
 */
class Public_api extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Bid_model');
    }

    public function featured_today()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        $this->require_api_key('public');

        $today = date('Y-m-d');
        $featured = $this->Bid_model->get_featured_for_date($today);

        if (!$featured) {
            return $this->json_response([
                'status' => false,
                'message' => 'No featured alumnus found for today.'
            ], 404);
        }

        return $this->json_response([
            'status' => true,
            'message' => 'Today\'s featured alumnus fetched successfully.',
            'data' => [
                'feature_date' => $today,
                'featured_alumnus' => [
                    'user_id' => (int) $featured['user_id'],
                    'full_name' => $featured['full_name'],
                    'email' => $featured['email'],
                    'headline' => $featured['headline'],
                    'biography' => $featured['biography'],
                    'linkedin_url' => $featured['linkedin_url'],
                    'profile_image' => $featured['profile_image'] ? base_url($featured['profile_image']) : null,
                    'current_job_title' => $featured['current_job_title'],
                    'current_company' => $featured['current_company'],
                    'degrees' => $featured['degrees']
                ]
            ]
        ]);
    }

    public function featured_by_date()
        {
            if ($this->input->method(TRUE) !== 'GET') {
                return $this->method_not_allowed();
            }

            $this->require_api_key('public');

            $date = trim((string) $this->input->get('date', true));

            if ($date === '') {
                return $this->validation_error([
                    'date' => 'Date is required.'
                ]);
            }

            $d = DateTime::createFromFormat('Y-m-d', $date);
            if (!$d || $d->format('Y-m-d') !== $date) {
                return $this->validation_error([
                    'date' => 'Date must be in YYYY-MM-DD format.'
                ]);
            }

            $featured = $this->Bid_model->get_featured_for_date($date);

            if (!$featured) {
                return $this->json_response([
                    'status' => false,
                    'message' => 'No featured alumnus found for this date.'
                ], 404);
            }

            return $this->json_response([
                'status' => true,
                'message' => 'Featured alumnus fetched successfully.',
                'data' => [
                    'feature_date' => $date,
                    'featured_alumnus' => [
                        'user_id' => (int) $featured['user_id'],
                        'full_name' => $featured['full_name'],
                        'email' => $featured['email'],
                        'headline' => $featured['headline'],
                        'biography' => $featured['biography'],
                        'linkedin_url' => $featured['linkedin_url'],
                        'profile_image' => $featured['profile_image'] ? base_url($featured['profile_image']) : null,
                        'current_job_title' => $featured['current_job_title'],
                        'current_company' => $featured['current_company'],
                        'degrees' => $featured['degrees']
                    ]
                ]
            ]);
        }
}
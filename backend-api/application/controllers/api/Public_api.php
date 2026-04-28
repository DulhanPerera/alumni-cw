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
        // The public featured endpoints only need the bidding model.
        $this->load->model('Bid_model');
    }

    public function featured_today()
    {
        // Keep this endpoint read-only.
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        // Require a valid public API key before exposing profile data.
        $this->require_api_key('public');

        $today = date('Y-m-d');
        $featured = $this->Bid_model->get_featured_for_date($today);

        if (!$featured) {
            // Surface a clear 404 when no feature exists for the current date.
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
            // Keep this endpoint read-only.
            if ($this->input->method(TRUE) !== 'GET') {
                return $this->method_not_allowed();
            }

            // Require a valid public API key before exposing profile data.
            $this->require_api_key('public');

            $date = trim((string) $this->input->get('date', true));

            if ($date === '') {
                // Make the missing-query-parameter case explicit.
                return $this->validation_error([
                    'date' => 'Date is required.'
                ]);
            }

            // Validate the query parameter strictly as YYYY-MM-DD.
            $d = DateTime::createFromFormat('Y-m-d', $date);
            if (!$d || $d->format('Y-m-d') !== $date) {
                return $this->validation_error([
                    'date' => 'Date must be in YYYY-MM-DD format.'
                ]);
            }

            $featured = $this->Bid_model->get_featured_for_date($date);

            if (!$featured) {
                // Keep the response consistent with the today endpoint.
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
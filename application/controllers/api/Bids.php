<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Session $session
 * @property CI_Input $input
 * @property Bid_model $Bid_model
 * @property User_model $User_model
 */
class Bids extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Bid_model');
        $this->load->library(['session']);
    }

    public function place_bid()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $user_id = $this->require_login();
        $data = $this->get_json_input();

        $bid_date = trim((string) ($data['bid_date'] ?? ''));
        $bid_amount = isset($data['bid_amount']) ? (float) $data['bid_amount'] : 0;

        $errors = [];

        if ($bid_date === '') {
            $errors['bid_date'] = 'Bid date is required.';
        } elseif (!$this->is_valid_date($bid_date)) {
            $errors['bid_date'] = 'Bid date must be in YYYY-MM-DD format.';
        }

        if ($bid_amount <= 0) {
            $errors['bid_amount'] = 'Bid amount must be greater than 0.';
        }

        if (!empty($errors)) {
            return $this->validation_error($errors);
        }

        if ($bid_date < date('Y-m-d')) {
            return $this->validation_error([
                'bid_date' => 'You cannot place a bid for a past date.'
            ]);
        }

        $month_key = date('Y-m', strtotime($bid_date));
        $slots = $this->Bid_model->get_remaining_slots($user_id, $month_key);

        if ($slots['remaining_slots'] <= 0) {
            return $this->json_response([
                'status' => false,
                'message' => 'Monthly featured limit reached.',
                'data' => $slots
            ], 400);
        }

        $existing_bid = $this->Bid_model->get_bid_by_user_and_date($user_id, $bid_date);

        if ($existing_bid) {
            return $this->json_response([
                'status' => false,
                'message' => 'You already have a bid for this date. Use update bid instead.'
            ], 400);
        }

        $bid_id = $this->Bid_model->create_bid([
            'user_id' => $user_id,
            'bid_date' => $bid_date,
            'bid_amount' => $bid_amount,
            'status' => 'active',
            'is_winner' => 0
        ]);

        $created_bid = $this->Bid_model->get_bid_by_id_and_user($bid_id, $user_id);
        $blind_status = $this->Bid_model->build_blind_status_for_bid($created_bid);

        return $this->json_response([
            'status' => true,
            'message' => 'Bid placed successfully.',
            'data' => [
                'bid_id' => $bid_id,
                'bid_date' => $bid_date,
                'your_bid_amount' => $bid_amount,
                'blind_status' => $blind_status
            ]
        ], 201);
    }

    public function update_bid($bid_id)
    {
        if ($this->input->method(TRUE) !== 'PUT') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $user_id = $this->require_login();
        $data = $this->get_json_input();

        $new_bid_amount = isset($data['bid_amount']) ? (float) $data['bid_amount'] : 0;

        if ($new_bid_amount <= 0) {
            return $this->validation_error([
                'bid_amount' => 'Bid amount must be greater than 0.'
            ]);
        }

        $bid = $this->Bid_model->get_bid_by_id_and_user((int) $bid_id, $user_id);

        if (!$bid) {
            return $this->json_response([
                'status' => false,
                'message' => 'Bid not found.'
            ], 404);
        }

        if ($bid['status'] === 'cancelled' || (int) $bid['is_winner'] === 1) {
            return $this->json_response([
                'status' => false,
                'message' => 'This bid can no longer be updated.'
            ], 400);
        }

        if ($new_bid_amount <= (float) $bid['bid_amount']) {
            return $this->validation_error([
                'bid_amount' => 'Updated bid must be higher than the current bid amount.'
            ]);
        }

        $this->Bid_model->update_bid_amount((int) $bid_id, $user_id, [
            'bid_amount' => $new_bid_amount,
            'status' => 'active'
        ]);

        $updated_bid = $this->Bid_model->get_bid_by_id_and_user((int) $bid_id, $user_id);
        $blind_status = $this->Bid_model->build_blind_status_for_bid($updated_bid);

        return $this->json_response([
            'status' => true,
            'message' => 'Bid updated successfully.',
            'data' => [
                'bid_id' => (int) $bid_id,
                'bid_date' => $updated_bid['bid_date'],
                'your_bid_amount' => $new_bid_amount,
                'blind_status' => $blind_status
            ]
        ]);
    }

    public function my_bid_status()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $user_id = $this->require_login();

        $bids = $this->Bid_model->get_user_bids($user_id);
        $result = [];

        foreach ($bids as $bid) {
            $result[] = [
                'id' => (int) $bid['id'],
                'bid_date' => $bid['bid_date'],
                'your_bid_amount' => (float) $bid['bid_amount'],
                'status' => $bid['status'],
                'is_winner' => (int) $bid['is_winner'],
                'blind_status' => $this->Bid_model->build_blind_status_for_bid($bid),
                'created_at' => $bid['created_at'],
                'updated_at' => $bid['updated_at']
            ];
        }

        return $this->json_response([
            'status' => true,
            'message' => 'Bid status fetched successfully.',
            'data' => [
                'bids' => $result
            ]
        ]);
    }

    public function remaining_slots()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $user_id = $this->require_login();

        $month = trim((string) $this->input->get('month', true));
        $month_key = $month !== '' ? $month : date('Y-m');

        if (!preg_match('/^\d{4}-\d{2}$/', $month_key)) {
            return $this->validation_error([
                'month' => 'Month must be in YYYY-MM format.'
            ]);
        }

        $slots = $this->Bid_model->get_remaining_slots($user_id, $month_key);

        return $this->json_response([
            'status' => true,
            'message' => 'Remaining monthly slots fetched successfully.',
            'data' => [
                'month' => $month_key,
                'wins_used' => $slots['wins_used'],
                'max_slots' => $slots['max_slots'],
                'remaining_slots' => $slots['remaining_slots'],
                'has_event_bonus' => $slots['has_event_bonus']
            ]
        ]);
    }

    public function select_winner()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->method_not_allowed();
        }

        $data = $this->get_json_input();
        $feature_date = trim((string) ($data['feature_date'] ?? ''));

        if ($feature_date === '') {
            return $this->validation_error([
                'feature_date' => 'Feature date is required.'
            ]);
        }

        if (!$this->is_valid_date($feature_date)) {
            return $this->validation_error([
                'feature_date' => 'Feature date must be in YYYY-MM-DD format.'
            ]);
        }

        $result = $this->Bid_model->mark_winner_and_feature($feature_date);

        if (!$result['success']) {
            return $this->json_response([
                'status' => false,
                'message' => $result['message']
            ], 400);
        }

        return $this->json_response([
            'status' => true,
            'message' => $result['message'],
            'data' => [
                'winner_bid_id' => (int) $result['winner_bid']['id'],
                'winner_user_id' => (int) $result['winner_bid']['user_id'],
                'feature_date' => $feature_date
            ]
        ]);
    }

    private function enforce_auth()
    {
        $last_activity = (int) $this->session->userdata('last_activity');

        if ($last_activity > 0 && (time() - $last_activity) > 1800) {
            $login_log_id = (int) $this->session->userdata('login_log_id');

            if ($login_log_id > 0) {
                $this->load->model('User_model');
                $this->User_model->mark_logout_log($login_log_id);
            }

            $this->session->sess_destroy();
            $this->unauthorized('Session expired.');
            exit;
        }

        $this->session->set_userdata('last_activity', time());
    }

    private function is_valid_date($date)
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
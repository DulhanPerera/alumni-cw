<!-- Name - Dulhan Perera -->
<!-- IIT ID: 20210165 -->
<!-- UoW ID: w1912842 -->

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
        // Load the bidding model and session library once for the whole controller.
        $this->load->model('Bid_model');
        $this->load->library(['session']);
    }

    public function place_bid()
    {
        // This endpoint only accepts JSON POST requests.
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->method_not_allowed();
        }

        // Enforce the logged-in session before creating a bid.
        $this->enforce_auth();
        $user_id = $this->require_login();
        $data = $this->get_json_input();

        $bid_date = trim((string) ($data['bid_date'] ?? ''));
        $bid_amount = isset($data['bid_amount']) ? (float) $data['bid_amount'] : 0;

        $errors = [];

        // Validate the bid date first so later logic can trust the value.
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

        // Prevent back-dated bidding.
        if ($bid_date < date('Y-m-d')) {
            return $this->validation_error([
                'bid_date' => 'You cannot place a bid for a past date.'
            ]);
        }

        // Check whether the user still has monthly capacity.
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
            // Enforce one active bid per day per user.
            return $this->json_response([
                'status' => false,
                'message' => 'You already have a bid for this date. Use update bid instead.'
            ], 400);
        }

        // Create the bid with an initial active state.
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
        // This endpoint only accepts JSON PUT requests.
        if ($this->input->method(TRUE) !== 'PUT') {
            return $this->method_not_allowed();
        }

        // Reuse the same session guard as bid creation.
        $this->enforce_auth();
        $user_id = $this->require_login();
        $data = $this->get_json_input();

        $new_bid_amount = isset($data['bid_amount']) ? (float) $data['bid_amount'] : 0;

        // Require a positive replacement amount.
        if ($new_bid_amount <= 0) {
            return $this->validation_error([
                'bid_amount' => 'Bid amount must be greater than 0.'
            ]);
        }

        // Confirm ownership before allowing the update.
        $bid = $this->Bid_model->get_bid_by_id_and_user((int) $bid_id, $user_id);

        if (!$bid) {
            return $this->json_response([
                'status' => false,
                'message' => 'Bid not found.'
            ], 404);
        }

        if ($bid['status'] === 'cancelled' || (int) $bid['is_winner'] === 1) {
            // Winning or cancelled bids are intentionally immutable.
            return $this->json_response([
                'status' => false,
                'message' => 'This bid can no longer be updated.'
            ], 400);
        }

        // Require a strict increase to keep the auction logic meaningful.
        if ($new_bid_amount <= (float) $bid['bid_amount']) {
            return $this->validation_error([
                'bid_amount' => 'Updated bid must be higher than the current bid amount.'
            ]);
        }

        // Reset to active because the user has improved the bid.
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
        // This endpoint only exposes the current user's bid history.
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        // Load the same session guard used for write operations.
        $this->enforce_auth();
        $user_id = $this->require_login();

        // Convert the raw database rows into a stable API shape.
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
        // This endpoint only accepts read-only requests.
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        // Enforce session freshness before exposing quota information.
        $this->enforce_auth();
        $user_id = $this->require_login();

        $month = trim((string) $this->input->get('month', true));
        $month_key = $month !== '' ? $month : date('Y-m');

        // Keep the month parameter strict so the model receives a predictable key.
        if (!preg_match('/^\d{4}-\d{2}$/', $month_key)) {
            return $this->validation_error([
                'month' => 'Month must be in YYYY-MM format.'
            ]);
        }

        // Report the quota breakdown directly to the client.
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
        // This endpoint only accepts requests that trigger the selection flow.
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->method_not_allowed();
        }

        $data = $this->get_json_input();
        $feature_date = trim((string) ($data['feature_date'] ?? ''));

        // The date must be present and strict before the model can process it.
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

        // Delegate the actual winner selection to the model transaction.
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
        // Reject stale sessions before the next action uses them.
        $last_activity = (int) $this->session->userdata('last_activity');

        if ($last_activity > 0 && (time() - $last_activity) > 1800) {
            $login_log_id = (int) $this->session->userdata('login_log_id');

            if ($login_log_id > 0) {
                // Close the audit trail before destroying the session.
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
        // Strictly parse the requested date instead of relying on loose comparisons.
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
<!-- Name - Dulhan Perera -->
<!-- IIT ID: 20210165 -->
<!-- UoW ID: w1912842 -->

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bid_model extends CI_Model
{
    private $bids_table = 'bids';
    private $featured_table = 'featured_alumni';
    private $events_table = 'alumni_events';
    private $event_participation_table = 'event_participation';
    private $profiles_table = 'profiles';
    private $degrees_table = 'degrees';

    public function get_bid_by_id_and_user($bid_id, $user_id)
    {
        // Load one bid only when it belongs to the requesting user.
        $row = $this->db
            ->get_where($this->bids_table, [
                'id' => $bid_id,
                'user_id' => $user_id
            ])
            ->row_array();

        return $row ?: null;
    }

    public function get_bid_by_user_and_date($user_id, $bid_date)
    {
        // Enforce the one-bid-per-user-per-day rule.
        $row = $this->db
            ->get_where($this->bids_table, [
                'user_id' => $user_id,
                'bid_date' => $bid_date
            ])
            ->row_array();

        return $row ?: null;
    }

    public function create_bid($data)
    {
        // Insert a new bid and return its ID to the controller.
        $this->db->insert($this->bids_table, $data);
        return (int) $this->db->insert_id();
    }

    public function update_bid_amount($bid_id, $user_id, $data)
    {
        // Update only the bid owned by the current user.
        return $this->db
            ->where('id', $bid_id)
            ->where('user_id', $user_id)
            ->update($this->bids_table, $data);
    }

    public function get_user_bids($user_id)
    {
        // Return bids newest-first so the API response is easy to scan.
        return $this->db
            ->order_by('bid_date', 'DESC')
            ->order_by('created_at', 'DESC')
            ->get_where($this->bids_table, ['user_id' => $user_id])
            ->result_array();
    }

    public function get_highest_bid_for_date($bid_date)
    {
        // The winner is the highest active bid, with earliest creation time as tie-breaker.
        $row = $this->db
            ->where('bid_date', $bid_date)
            ->where('status !=', 'cancelled')
            ->order_by('bid_amount', 'DESC')
            ->order_by('created_at', 'ASC')
            ->get($this->bids_table, 1)
            ->row_array();

        return $row ?: null;
    }

    public function get_all_active_bids_for_date($bid_date)
    {
        // Used when the UI or an admin view needs the full active leaderboard.
        return $this->db
            ->where('bid_date', $bid_date)
            ->where('status !=', 'cancelled')
            ->order_by('bid_amount', 'DESC')
            ->order_by('created_at', 'ASC')
            ->get($this->bids_table)
            ->result_array();
    }

    public function count_monthly_wins($user_id, $year_month)
    {
        // Count featured placements already consumed in the month.
        $start = $year_month . '-01';
        $end = date('Y-m-t', strtotime($start));

        return (int) $this->db
            ->where('user_id', $user_id)
            ->where('feature_date >=', $start)
            ->where('feature_date <=', $end)
            ->count_all_results($this->featured_table);
    }

    public function has_event_participation_in_month($user_id, $year_month)
    {
        // Check whether the user qualifies for the event bonus slot.
        $start = $year_month . '-01';
        $end = date('Y-m-t', strtotime($start));

        $row = $this->db
            ->select('ep.id')
            ->from($this->event_participation_table . ' ep')
            ->join($this->events_table . ' ae', 'ae.id = ep.event_id')
            ->where('ep.user_id', $user_id)
            ->where('ae.event_date >=', $start)
            ->where('ae.event_date <=', $end)
            ->limit(1)
            ->get()
            ->row_array();

        return !empty($row);
    }

    public function get_remaining_slots($user_id, $year_month)
    {
        // Derive the remaining monthly allowance from wins and event participation.
        $wins = $this->count_monthly_wins($user_id, $year_month);
        $has_event = $this->has_event_participation_in_month($user_id, $year_month);

        $max_slots = $has_event ? 4 : 3;
        $remaining = $max_slots - $wins;

        if ($remaining < 0) {
            $remaining = 0;
        }

        return [
            'wins_used' => $wins,
            'max_slots' => $max_slots,
            'remaining_slots' => $remaining,
            'has_event_bonus' => $has_event
        ];
    }

    public function build_blind_status_for_bid($bid)
    {
        // Hide outcome details from everyone except the current top bidder.
        $highest = $this->get_highest_bid_for_date($bid['bid_date']);

        if (!$highest) {
            return 'pending';
        }

        if ((int) $highest['id'] === (int) $bid['id']) {
            return 'winning';
        }

        return 'outbid';
    }

    public function featured_exists_for_date($feature_date)
    {
        // Guard against selecting a winner twice for the same day.
        return $this->db
            ->where('feature_date', $feature_date)
            ->count_all_results($this->featured_table) > 0;
    }

    public function mark_winner_and_feature($bid_date)
    {
        // Wrap the selection process in a transaction so the winner and featured row stay in sync.
        $this->db->trans_start();

        $winner = $this->get_highest_bid_for_date($bid_date);

        if (!$winner) {
            $this->db->trans_complete();
            return [
                'success' => false,
                'message' => 'No bids found for this date.'
            ];
        }

        if ($this->featured_exists_for_date($bid_date)) {
            $this->db->trans_complete();
            return [
                'success' => false,
                'message' => 'Winner already selected for this date.'
            ];
        }

        $month_key = date('Y-m', strtotime($bid_date));
        $slots = $this->get_remaining_slots((int) $winner['user_id'], $month_key);

        if ($slots['remaining_slots'] <= 0) {
            // If the bidder is over quota, mark the bid as lost and stop.
            $this->db
                ->where('id', $winner['id'])
                ->update($this->bids_table, [
                    'status' => 'lost',
                    'is_winner' => 0
                ]);

            $this->db->trans_complete();
            return [
                'success' => false,
                'message' => 'Top bidder exceeded monthly featured limit.'
            ];
        }

        $this->db
            ->where('bid_date', $bid_date)
            ->where('id !=', $winner['id'])
            ->where('status !=', 'cancelled')
            ->update($this->bids_table, [
                'status' => 'lost',
                'is_winner' => 0
            ]);

        // Mark the winning bid before creating the featured-alumni record.
        $this->db
            ->where('id', $winner['id'])
            ->update($this->bids_table, [
                'status' => 'won',
                'is_winner' => 1
            ]);

        $this->db->insert($this->featured_table, [
            'user_id' => $winner['user_id'],
            'bid_id' => $winner['id'],
            'feature_date' => $bid_date
        ]);

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            // Return a single failure message if any write in the transaction failed.
            return [
                'success' => false,
                'message' => 'Failed to select winner.'
            ];
        }

        return [
            'success' => true,
            'message' => 'Winner selected successfully.',
            'winner_bid' => $winner
        ];
    }

    public function get_featured_for_date($feature_date)
    {
        // Join the featured row to the user and profile records for API output.
        $featured = $this->db
            ->select('fa.*, u.full_name, u.email, p.headline, p.biography, p.linkedin_url, p.profile_image, p.current_job_title, p.current_company')
            ->from($this->featured_table . ' fa')
            ->join('users u', 'u.id = fa.user_id')
            ->join($this->profiles_table . ' p', 'p.user_id = u.id', 'left')
            ->where('fa.feature_date', $feature_date)
            ->get()
            ->row_array();

        if (!$featured) {
            return null;
        }

        $degrees = [];
        if (!empty($featured['user_id'])) {
            // Load profile degrees separately so the API can return a nested list.
            $profile = $this->db
                ->select('id')
                ->get_where($this->profiles_table, ['user_id' => $featured['user_id']])
                ->row_array();

            if ($profile) {
                $degrees = $this->db
                    ->select('degree_name, institution_name, completion_date')
                    ->order_by('completion_date', 'DESC')
                    ->get_where($this->degrees_table, ['profile_id' => $profile['id']])
                    ->result_array();
            }
        }

        $featured['degrees'] = $degrees;

        return $featured;
    }
}
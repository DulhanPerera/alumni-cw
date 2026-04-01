<!-- Name - Dulhan Perera -->
<!-- IIT ID: 20210165 -->
<!-- UoW ID: w1912842 -->

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Internal scheduled tasks controller
 *
 * Example manual CLI run:
 * php index.php cron select_winner_today
 *
 * Example with explicit date:
 * php index.php cron select_winner_for_date 2026-04-02
 *
 * @property Bid_model $Bid_model
 */
class Cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Load the bidding model once for the scheduled winner selection flow.
        $this->load->model('Bid_model');
    }

    public function index()
    {
        // Lightweight smoke test for manual CLI execution.
        echo "Cron controller is working.";
    }

    public function select_winner_today()
    {
        // Use the current date when no explicit date is supplied.
        $feature_date = date('Y-m-d');
        $this->run_winner_selection($feature_date);
    }

    public function select_winner_for_date($feature_date = null)
    {
        // Reject empty input early so the CLI usage is explicit.
        if (empty($feature_date)) {
            echo "Error: feature date is required in YYYY-MM-DD format.\n";
            return;
        }

        // Keep the command interface strict to prevent accidental bad dates.
        if (!$this->is_valid_date($feature_date)) {
            echo "Error: invalid date format. Use YYYY-MM-DD.\n";
            return;
        }

        $this->run_winner_selection($feature_date);
    }

    private function run_winner_selection($feature_date)
    {
        // Delegate the business rule to the model and print a CLI-friendly result.
        $result = $this->Bid_model->mark_winner_and_feature($feature_date);

        if (!empty($result['success'])) {
            // Surface the winning bid so a scheduler log is informative.
            echo "SUCCESS: " . $result['message'] . "\n";
            echo "Feature date: " . $feature_date . "\n";

            if (!empty($result['winner_bid'])) {
                echo "Winner bid ID: " . $result['winner_bid']['id'] . "\n";
                echo "Winner user ID: " . $result['winner_bid']['user_id'] . "\n";
                echo "Winning amount: " . $result['winner_bid']['bid_amount'] . "\n";
            }
        } else {
            // Show the failure reason directly in the command output.
            echo "FAILED: " . $result['message'] . "\n";
            echo "Feature date: " . $feature_date . "\n";
        }
    }

    private function is_valid_date($date)
    {
        // Parse and re-format the date to ensure the value is not loosely matched.
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
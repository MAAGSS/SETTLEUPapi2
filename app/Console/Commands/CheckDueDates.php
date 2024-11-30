<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Debtor;
use App\Http\Controllers\SMSController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\DueDateReminder;

class CheckDueDates extends Command
{
    protected $signature = 'check:duedates';
    protected $description = 'Check debtors with due dates near and send SMS reminders';

    public function handle()
    {
        // Fetch all debtors
        $debtors = Debtor::all();

        if ($debtors->isEmpty()) {
            $this->error('No debtors found in the database.');
            return;
        }

        foreach ($debtors as $debtor) {
            try {
                // Calculate days remaining until the due date (reverse the order)
$dueDate = Carbon::parse($debtor->due_date);
$daysRemaining = Carbon::now()->diffInDays($dueDate, false); // false allows negative values if overdue
$daysRemaining = round($daysRemaining);
$this->info("Days remaining for debtor {$debtor->contact_number}: $daysRemaining");

// Check different conditions based on the number of days remaining
if ($daysRemaining == 0) {
    // Due today
    $message = "Reminder: Your payment of {$debtor->total_amount} is due today. Please settle it promptly to avoid penalties.";
} elseif ($daysRemaining > 0 && $daysRemaining <= 3) {
    // Due in 1-3 days
    $message = "Reminder: Your payment of {$debtor->total_amount} is due in {$daysRemaining} day" . ($daysRemaining > 1 ? 's' : '') . " ({$debtor->due_date}). Please settle it to avoid penalties.";
} elseif ($daysRemaining > 3) {
    // Due in more than 3 days
    $message = "Reminder: Your payment of {$debtor->total_amount} is due on {$debtor->due_date}. Please be sure to settle it before the due date to avoid penalties.";
} elseif ($daysRemaining < 0) {
    // Overdue (if the due date has already passed)
    $message = "Your payment of {$debtor->total_amount} was due on {$debtor->due_date}. Please make the payment immediately to avoid further penalties.";
} else {
    // No reminder if the due date is not within any specified condition
    continue;
}


                // Log the SMS sending attempt
                $this->info("Sending SMS to {$debtor->contact_number}: $message");

                // Send SMS reminder
                $smsController = new SMSController();
                $response = $smsController->sendSMS(new \Illuminate\Http\Request([
                    'to' => $debtor->contact_number,
                    'message' => $message
                ]));

                // Log the response (use Log facade)
                Log::info('SMS response:', json_decode($response->getContent(), true));

                $this->info("Reminder SMS sent to {$debtor->contact_number}");

                // Send email reminder
                $this->info("Sending email to {$debtor->email}.");
                Mail::to($debtor->email)->send(new DueDateReminder($debtor));

                $this->info("Reminder email sent to {$debtor->email}");
            } catch (\Exception $e) {
                $this->error("Failed to send reminder to {$debtor->contact_number}: " . $e->getMessage());
            }
        }

        $this->info('Due date check and reminders completed.');
    }
}

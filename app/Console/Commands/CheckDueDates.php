<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Debtor;
use App\Http\Controllers\SMSController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\DueDateReminder;
use App\Models\NotificationsLog;

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
                $userId = $debtor->user_id; // Get user_id from the debtor record
        
                // Calculate days remaining until the due date
                $dueDate = Carbon::parse($debtor->due_date);
                $daysRemaining = Carbon::now()->diffInDays($dueDate, false);
                $daysRemaining = round($daysRemaining);
        
                $this->info("Days remaining for debtor {$debtor->contact_number}: $daysRemaining");
        
                // Determine the message
                if ($daysRemaining == 0) {
                    $message = "Reminder: Your payment of {$debtor->total_amount} is due today. Please settle it promptly to avoid penalties.";
                } elseif ($daysRemaining > 0 && $daysRemaining <= 3) {
                    $message = "Reminder: Your payment of {$debtor->total_amount} is due in {$daysRemaining} day" . ($daysRemaining > 1 ? 's' : '') . " ({$debtor->due_date}). Please settle it to avoid penalties.";
                } elseif ($daysRemaining > 3) {
                    $message = "Reminder: Your payment of {$debtor->total_amount} is due on {$debtor->due_date}. Please be sure to settle it before the due date to avoid penalties.";
                } elseif ($daysRemaining < 0) {
                    $message = "Your payment of {$debtor->total_amount} was due on {$debtor->due_date}. Please make the payment immediately to avoid further penalties.";
                } else {
                    continue;
                }
        
                // Log the SMS attempt in the database
                NotificationsLog::create([
                    'user_id' => $debtor->usr_id,
                    'contact_number' => $debtor->contact_number,
                    'email' => $debtor->email,
                    'message' => $message,
                    'type' => 'SMS',
                    'status' => 'Pending',
                ]);
        
                // Send SMS reminder
                $smsController = new SMSController();
                $response = $smsController->sendSMS(new \Illuminate\Http\Request([
                    'to' => $debtor->contact_number,
                    'message' => $message
                ]));
        
                // Log success
                NotificationsLog::where('contact_number', $debtor->contact_number)
                    ->latest()
                    ->first()
                    ->update(['status' => 'Success', 'error_message' => null]);
        
                $this->info("Reminder SMS sent to {$debtor->contact_number}");
        
                // Send email reminder
                Mail::to($debtor->email)->send(new DueDateReminder($debtor));
        
                $this->info("Reminder email sent to {$debtor->email}");
            } catch (\Exception $e) {
                // Log failure
                NotificationsLog::create([
                    'user_id' => $debtor->usr_id,
                    'contact_number' => $debtor->contact_number,
                    'email' => $debtor->email,
                    'message' => $message,
                    'type' => 'SMS/Email',
                    'status' => 'Failed',
                    'error_message' => $e->getMessage(),
                ]);
        
                $this->error("Failed to send reminder to {$debtor->contact_number}: " . $e->getMessage());
            }
        }
}
}
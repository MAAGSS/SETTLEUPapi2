<?
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Debtor;
use App\Http\Controllers\SMSController;
use Carbon\Carbon;
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
                $userId = $debtor->usr_id; // Get user_id from the debtor record
        
                // Calculate days remaining until the due date
                $dueDate = Carbon::parse($debtor->due_date);
                $daysRemaining = Carbon::now()->diffInDays($dueDate, false);
                $daysRemaining = round($daysRemaining);
        
                $this->info("Days remaining for debtor {$debtor->contact_number}: $daysRemaining");
        
                // Determine the message and frequency based on days remaining
                if ($daysRemaining > 30) {
                    // More than a month - Send once a week
                    $message = "Reminder: Your payment of {$debtor->total_amount} is due on {$debtor->due_date}. Please be sure to settle it before the due date to avoid penalties.";
                    $sendFrequency = 'weekly'; // Send once a week
                } elseif ($daysRemaining <= 30 && $daysRemaining > 14) {
                    // More than 2 weeks but less than a month - Send twice a week
                    $message = "Reminder: Your payment of {$debtor->total_amount} is due in {$daysRemaining} day" . ($daysRemaining > 1 ? 's' : '') . " ({$debtor->due_date}). Please settle it to avoid penalties.";
                    $sendFrequency = 'biweekly'; // Send twice a week
                } elseif ($daysRemaining <= 14 && $daysRemaining > 7) {
                    // Less than 2 weeks but more than 1 week - Send three times a week
                    $message = "Reminder: Your payment of {$debtor->total_amount} is due in {$daysRemaining} day" . ($daysRemaining > 1 ? 's' : '') . " ({$debtor->due_date}). Please settle it promptly to avoid penalties.";
                    $sendFrequency = 'thriceweekly'; // Send three times a week
                } elseif ($daysRemaining <= 7 && $daysRemaining > 0) {
                    // Less than 1 week - Send daily reminders
                    $message = "Reminder: Your payment of {$debtor->total_amount} is due on {$debtor->due_date}. Please make sure to settle it immediately to avoid penalties.";
                    $sendFrequency = 'daily'; // Send daily
                } elseif ($daysRemaining == 0) {
                    // Due today - Send a final reminder
                    $message = "Reminder: Your payment of {$debtor->total_amount} is due today. Please settle it promptly to avoid penalties.";
                    $sendFrequency = 'once'; // Send just once
                } elseif ($daysRemaining < 0) {
                    // Due already - Send overdue reminder
                    $message = "Your payment of {$debtor->total_amount} was due on {$debtor->due_date}. Please make the payment immediately to avoid further penalties.";
                    $sendFrequency = 'daily'; // Send just once for overdue
                } else {
                    continue; // Skip if no valid condition
                }
        
                // Check if the reminder was already sent within the required frequency
                $lastReminder = NotificationsLog::where('contact_number', $debtor->contact_number)
                    ->where('status', 'Success')
                    ->latest()
                    ->first();

                // Get the frequency condition
                $shouldSendReminder = false;
                if ($sendFrequency == 'weekly' && (!$lastReminder || Carbon::parse($lastReminder->created_at)->diffInDays(Carbon::now()) >= 7)) {
                    $shouldSendReminder = true;
                } elseif ($sendFrequency == 'biweekly' && (!$lastReminder || Carbon::parse($lastReminder->created_at)->diffInDays(Carbon::now()) >= 3)) {
                    $shouldSendReminder = true;
                } elseif ($sendFrequency == 'thriceweekly' && (!$lastReminder || Carbon::parse($lastReminder->created_at)->diffInDays(Carbon::now()) >= 2)) {
                    $shouldSendReminder = true;
                } elseif ($sendFrequency == 'daily' && (!$lastReminder || Carbon::parse($lastReminder->created_at)->diffInDays(Carbon::now()) >= 1)) {
                    $shouldSendReminder = true;
                } elseif ($sendFrequency == 'once' && !$lastReminder) {
                    $shouldSendReminder = true;
                }

                // If we should send a reminder, send it
                if ($shouldSendReminder) {
                    // Send SMS reminder
                    $smsController = new SMSController();
                    $response = $smsController->sendSMS(new \Illuminate\Http\Request([
                        'to' => $debtor->contact_number,
                        'message' => $message
                    ]));
        
                    $this->info("Reminder SMS sent to {$debtor->contact_number}");
        
                    // Send email reminder
                    Mail::to($debtor->email)->send(new DueDateReminder($debtor));
        
                    $this->info("Reminder email sent to {$debtor->email}");
                } else {
                    $this->info("Skipping reminder for {$debtor->contact_number} as it was sent recently.");
                }

            } catch (\Exception $e) {
                // Log failure
                NotificationsLog::create([
                    'user_id' => $debtor->usr_id,
                    'contact_number' => $debtor->contact_number,
                    'email' => $debtor->email,
                    'message' => $message ?? 'Error occurred while sending reminder.',
                    'type' => 'SMS/Email',
                    'status' => 'Failed',
                    'error_message' => $e->getMessage(),
                ]);
        
                $this->error("Failed to send reminder to {$debtor->contact_number}: " . $e->getMessage());
            }
        }
    }
}

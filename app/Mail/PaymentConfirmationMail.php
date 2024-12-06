<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Debtor;

class PaymentConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $debtor;
    public $paymentAmount;
    public $remainingBalance;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Debtor $debtor, $paymentAmount)
    {
        $this->debtor = $debtor;
        $this->paymentAmount = $paymentAmount;
        $this->remainingBalance = $debtor->total_amount;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Payment Confirmation')
                    ->view('emails.payment_confirmation')
                    ->with([
                        'debtorName' => $this->debtor->name,
                        'paymentAmount' => $this->paymentAmount,
                        'remainingBalance' => $this->remainingBalance,
                        'dueDate' => $this->debtor->due_date
                    ]);
    }
}
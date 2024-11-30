<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DueDateReminder extends Mailable
{
    public $debtor;

    public function __construct($debtor)
    {
        $this->debtor = $debtor;
    }

    public function build()
    {
        return $this->subject('Reminder: Upcoming Payment Due')
                    ->view('emails.due_date_reminder')
                    ->with(['debtor' => $this->debtor]);
    }
}
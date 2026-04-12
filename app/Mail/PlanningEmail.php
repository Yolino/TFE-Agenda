<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanningEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $fromAddress;
    public $fromName;
    public $subject;
    public $attachmentPath;
    public $dateRange;

    public function __construct($fromAddress, $fromName, $subject, $attachmentPath, $dateRange)
    {
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
        $this->subject = $subject;
        $this->attachmentPath = $attachmentPath;
        $this->dateRange = $dateRange;
    }

    public function build()
    {
        return $this->view('emails.planning')
            ->from($this->fromAddress, $this->fromName)
            ->subject($this->subject)
            ->attach($this->attachmentPath);
    }
}

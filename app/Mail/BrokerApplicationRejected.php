<?php

namespace App\Mail;

use App\Models\BrokerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BrokerApplicationRejected extends Mailable
{
    use Queueable, SerializesModels;

    public BrokerApplication $application;

    public function __construct(BrokerApplication $application)
    {
        $this->application = $application;
    }

    public function build(): self
    {
        return $this->subject('Broker Application Review Result')
            ->view('mail.broker-application-rejected');
    }
}

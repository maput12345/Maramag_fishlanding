<?php

namespace App\Mail;

use App\Models\ApplicationOpening;
use App\Models\BrokerApplication;
use App\Models\Stall;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BrokerApplicationQualifiedForBidding extends Mailable
{
    use Queueable, SerializesModels;

    public BrokerApplication $application;
    public ApplicationOpening $opening;
    public Stall $stall;

    public function __construct(BrokerApplication $application, ApplicationOpening $opening, Stall $stall)
    {
        $this->application = $application;
        $this->opening = $opening;
        $this->stall = $stall;
    }

    public function build(): self
    {
        return $this->subject('You Are Qualified for the Stall Bidding')
            ->view('mail.broker-application-qualified');
    }
}

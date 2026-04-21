<?php

namespace App\Mail;

use App\Models\Broker;
use App\Models\Stall;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BrokerWinnerSelected extends Mailable
{
    use Queueable, SerializesModels;

    public Broker $broker;
    public Stall $stall;

    public function __construct(Broker $broker, Stall $stall)
    {
        $this->broker = $broker;
        $this->stall = $stall;
    }

    public function build(): self
    {
        return $this->subject('Maramag Fish Landing Broker Application Result')
            ->view('mail.broker-winner-selected');
    }
}

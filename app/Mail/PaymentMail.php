<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;
    public $messageText;
    public $paymentValue;
    public $paymentUrl;
    public $subjetcText;

    /**
     * Create a new message instance.
     */
    public function __construct(
        $userName,
        $paymentValue,
        $paymentUrl,
        $subjetc
    )
    {
        $this->userName = $userName;
        $this->paymentValue = $paymentValue;
        $this->paymentUrl = $paymentUrl;
        $this->subjetcText = $subjetc;
    }

    /**
     * Get the message envelope.
     */
    
    public function build()
    {
        return $this->view('emails.paymentMail')
                    ->with([
                        'name' => $this->userName,
                        'value' => $this->paymentValue,
                        'url' => $this->paymentUrl,
                    ])
                    ->subject($this->subjetcText);
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AnalisyContractMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $justification;
    public $textMessage;
    public $subject;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $subject, $message, $justification = null)
    {
        $this->name = $name;
        $this->justification = $justification;
        $this->textMessage = $message;
        $this->subject = $subject;
    }

    /**
     * Get the message envelope.
     */
    
    public function build()
    {
        return $this->view(view: 'emails.analisyContract')
                    ->with(key: [
                        'name' => $this->name,
                        'justification' => $this->justification,
                        'message' => $this->textMessage,
                    ])
                    ->subject(subject: $this->subject);
    }

}
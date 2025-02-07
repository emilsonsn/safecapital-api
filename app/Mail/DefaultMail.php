<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DefaultMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;
    public $messageText;
    public $subjetcText;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $message, $subjetc)
    {
        $this->userName = $name;
        $this->messageText = $message;
        $this->subjetcText = $subjetc;
    }

    /**
     * Get the message envelope.
     */
    
    public function build()
    {
        return $this->view('emails.defaultEmail')
                    ->with([
                        'name' => $this->userName,
                        'message' => $this->messageText,
                    ])
                    ->subject($this->subjetcText);
    }
}
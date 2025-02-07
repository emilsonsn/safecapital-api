<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DefaultMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $message;
    public $subjetc;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $message, $subjetc)
    {
        $this->name = $name;
        $this->message = $message;
        $this->subjetc = $subjetc;
    }

    /**
     * Get the message envelope.
     */
    
    public function build()
    {
        return $this->view('emails.default_email')
                    ->with([
                        'name' => $this->name,
                        'message' => $this->message,
                    ])
                    ->subject($this->subjetc);
    }
}
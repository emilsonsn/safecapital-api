<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ValidationReturnMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $justification;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $justification)
    {
        $this->name = $name;
        $this->justification = $justification;
    }

    /**
     * Get the message envelope.
     */
    
    public function build()
    {
        return $this->view('emails.requestReturn')
                    ->with([
                        'name' => $this->name,
                        'justification' => $this->justification,
                    ])
                    ->subject('Solicitação de cadastro com pendências');
    }

}
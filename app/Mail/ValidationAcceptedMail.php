<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ValidationAcceptedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $email;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $email)
    {
        $this->name = $name;
        $this->email = $email;
    }

    /**
     * Get the message envelope.
     */
    
    public function build()
    {
        return $this->view('emails.requestAccepted')
                    ->with([
                        'name' => $this->name,
                        'email' => $this->email,
                    ])
                    ->subject('Sua solicitação foi aceita!')
                    ->attach(base_path('resources/docs/termo_de_aceite_versao_1.0.pdf'));
    }
}
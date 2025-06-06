<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;


    /**
     * Create a new message instance.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get the message envelope.
     */
    
    public function build()
    {
        return $this->view('emails.welcome')
                    ->with([
                        'name' => $this->name,
                    ])
                    ->subject('Pedido de cadastro em análise');
    }

}

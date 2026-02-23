<?php

namespace App\Mail;

use App\Models\ClientInstallment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InstallmentBoletoMail extends Mailable
{
    use Queueable, SerializesModels;

    public ClientInstallment $installment;

    public function __construct(ClientInstallment $installment)
    {
        $this->installment = $installment;
    }

    public function build()
    {
        return $this->subject('Seu boleto está disponível')
            ->view('emails.installment');
    }
}
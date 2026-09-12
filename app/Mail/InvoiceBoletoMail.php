<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceBoletoMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public Invoice $invoice;

    public function __construct(User $user, Invoice $invoice)
    {
        $this->user = $user;
        $this->invoice = $invoice;
    }

    public function build()
    {
        return $this->subject('Sua fatura mensal está disponível')
            ->view('emails.invoice');
    }
}

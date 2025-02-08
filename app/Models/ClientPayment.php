<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPayment extends Model
{
    use HasFactory;

    public $table = 'client_payments';

    protected $fillable = [
        'external_id',
        'client_id',
        'status',
        'transaction_amount',
        'url',
        'payer',
    ];

    public function client(): BelongsTo{
        return $this->belongsTo(Client::class);
    }
}

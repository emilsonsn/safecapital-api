<?php

namespace App\Models;

use App\Enums\InvoiceStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Invoice extends Model
{
    protected $fillable = [
        'user_id', 'closing_date', 'due_date', 'amount', 'status',
        'provider_external_id', 'provider_correlation_id', 'digitable_line',
        'boleto_url', 'boleto_barcode', 'paid_at', 'meta',
    ];

    protected $casts = [
        'closing_date' => 'date', 'due_date' => 'date', 'amount' => 'float',
        'paid_at' => 'datetime', 'meta' => 'array', 'status' => InvoiceStatusEnum::class,
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function installments(): BelongsToMany
    {
        return $this->belongsToMany(ClientInstallment::class, 'invoice_items')
            ->withPivot('amount')->withTimestamps();
    }
}

<?php

namespace App\Models;

use App\Enums\InstallmentStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientInstallment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'installment_number',
        'amount',
        'paid_amount',
        'fine',
        'interest',
        'provider_external_id',
        'provider_correlation_id',
        'digitable_line',
        'meta',
        'boleto_url',
        'boleto_barcode',
        'boleto_pdf_path',
        'boleto_uploaded_path',
        'due_date',
        'boleto_sent_at',
        'paid_at',
        'status',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_amount' => 'float',
        'fine' => 'float',
        'interest' => 'float',
        'due_date' => 'date',
        'boleto_sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'meta' => 'array',
        'status' => InstallmentStatusEnum::class,
    ];

    protected $appends = [
        'installment_uploaded_url',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getInstallmentUploadedUrlAttribute()
    {
        return $this->attributes['boleto_uploaded_path']
            ? asset('storage/' . $this->attributes['boleto_uploaded_path'])
            : null;
    }
}
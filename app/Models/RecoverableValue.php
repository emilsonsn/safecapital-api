<?php

namespace App\Models;

use App\Enums\RecoverableStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecoverableValue extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference', 'case_number', 'counterparty', 'description', 'amount',
        'expected_recovery_date', 'received_at', 'resolved_at', 'status', 'notes',
        'created_by_user_id', 'resolved_by_user_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'expected_recovery_date' => 'date',
        'received_at' => 'datetime',
        'resolved_at' => 'datetime',
        'status' => RecoverableStatusEnum::class,
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}

<?php

namespace App\Models;

use App\Enums\BankIntegrationStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankIntegration extends Model
{
    protected $fillable = [
        'provider', 'environment', 'company_id', 'account_id', 'account_branch',
        'account_number', 'access_token', 'refresh_token', 'access_token_expires_at',
        'refresh_token_expires_at', 'scopes', 'status', 'authorized_by_user_id',
        'authorized_at', 'last_refreshed_at', 'last_error', 'meta',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'access_token_expires_at' => 'datetime',
        'refresh_token_expires_at' => 'datetime',
        'authorized_at' => 'datetime',
        'last_refreshed_at' => 'datetime',
        'scopes' => 'array',
        'meta' => 'array',
        'status' => BankIntegrationStatusEnum::class,
    ];

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by_user_id');
    }
}

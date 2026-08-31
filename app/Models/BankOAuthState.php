<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankOAuthState extends Model
{
    protected $table = 'bank_oauth_states';

    protected $fillable = ['provider', 'state_hash', 'user_id', 'expires_at', 'used_at'];

    protected $casts = ['expires_at' => 'datetime', 'used_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

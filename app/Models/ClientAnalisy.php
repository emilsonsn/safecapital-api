<?php

namespace App\Models;

use App\Enums\ClientStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientAnalisy extends Model
{
    use HasFactory;

    public $table = 'client_analisys';

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'cpf' => 'string',
        'score' => 'string',
        'has_pendings' => 'boolean',
        'has_processes' => 'boolean',
        'status' => ClientStatusEnum::class,
    ];

    protected $fillable = [
        'user_id',
        'cpf',
        'score',
        'has_pendings',
        'has_processes',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

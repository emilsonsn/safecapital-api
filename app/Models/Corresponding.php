<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Corresponding extends Model
{
    use HasFactory;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    public $table = 'correspondings';

    protected $fillable = [
        'client_id',
        'cpf',
        'fullname',
        'birthday',
        'declared_income',
        'occupation',
        'email',
        'phone',
    ];

    public function client(): BelongsTo {
        return $this->belongsTo(related: Client::class);
    }
}

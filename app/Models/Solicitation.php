<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Solicitation extends Model
{
    use HasFactory, SoftDeletes;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'solicitations';

    protected $fillable = [
        'contract_number',
        'subject',
        'status',
        'category',
        'user_id',
    ];

    public function user(): BelongsTo{
        return $this->belongsTo(related: User::class);
    }

    public function messages(): HasMany{
        return $this->hasMany(related: SolicitationMessage::class)->with('user');
    }

    public function attachments(): HasMany{
        return $this->hasMany(related: SolicitationAttachment::class);
    }

    public function items(): HasMany{
        return $this->hasMany(related: SolicitationItem::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SolicitationAttachment extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'solicitation_attachments';

    public $fillable = [
        'solicitation_id',
        'path'
    ];

    public function getPathAttribute($value): string|null{
        return isset($value) ? asset($value) : null;
    }

    public function solicitation(): BelongsTo{
        return $this->belongsTo(Solicitation::class);
    }
}
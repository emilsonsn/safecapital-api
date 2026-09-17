<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'text', 'image_path', 'active', 'order', 'created_by',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $appends = [
        'image_url',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->attributes['image_path']
            ? asset('storage/'.$this->attributes['image_path'])
            : null;
    }
}

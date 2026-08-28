<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermDocument extends Model
{
    use HasFactory;

    public $table = 'term_documents';

    protected $fillable = [
        'filename',
        'path',
        'external_url',
        'version',
        'uploaded_by',
    ];

    protected $appends = [
        'url',
    ];

    public function getUrlAttribute(): ?string
    {
        if ($this->external_url) {
            return $this->external_url;
        }

        return $this->path ? asset('storage/'.$this->path) : null;
    }
}

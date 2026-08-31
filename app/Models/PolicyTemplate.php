<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicyTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'path',
        'version',
        'uploaded_by',
    ];

    protected $hidden = [
        'path',
    ];
}

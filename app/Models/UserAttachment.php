<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAttachment extends Model
{
    use HasFactory, SoftDeletes;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'user_attachments';

    protected $fillable = [
        'category',
        'filename',
        'path',
        'user_id',
    ];

    public function getPathAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}

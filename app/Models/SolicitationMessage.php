<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SolicitationMessage extends Model
{
    use HasFactory, SoftDeletes;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'solicitation_messages';

    protected $fillable = [
        'message',
        'attachment',
        'solicitation_id',
        'user_id',
    ];

    public function solicitation(){
        return $this->belongsTo(Solicitation::class);
    }

    public function getAttachmentAttribute($attachment)
    {
        return $attachment ? asset('storage/' . $attachment) : null;
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientAttachment extends Model
{
    use HasFactory, SoftDeletes;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'client_attachments';

    protected $fillable = [
        'category',
        'filename',
        'path',
        'client_id',
    ];

    public function getPathAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }
    
    public function client(){
        return $this->belongsTo(Client::class);
    }
}

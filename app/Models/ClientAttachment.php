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
        'description',
        'filename',
        'path',
        'client_id',
    ];
    public function user(){
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicyDocument extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'policy_documents';

    public $fillable = [
        'filename',
        'path',
        'due_date',
        'client_id',
    ];
    
    public function client(){
        return $this->belongsTo(Client::class);
    }
}
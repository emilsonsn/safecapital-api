<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordRecovery extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'password_recovery';

    protected $fillable = [
        'code',
        'user_id',        
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

}

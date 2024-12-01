<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Solicitation extends Model
{
    use HasFactory, SoftDeletes;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'solicitations';

    protected $fillable = [
        'contract_number',
        'subject',
        'status',
        'user_id',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function messages(){
        return $this->hasMany(SolicitationMessage::class)->with('user');
    }
}

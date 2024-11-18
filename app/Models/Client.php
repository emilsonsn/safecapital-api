<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'clients';

    protected $fillable = [
        'name',
        'surname',
        'birthday',
        'email',
        'phone',
        'cpf',
        'cep',
        'street',
        'neighborhood',
        'city',
        'state',
        'user_id',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}

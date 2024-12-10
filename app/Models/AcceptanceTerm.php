<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcceptanceTerm extends Model
{
    use HasFactory;

    use HasFactory;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'acceptance_terms';

    protected $fillable = [
        'ip',
        'user_id',
        'terms_version'
    ];

    public function getPathAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

   
}

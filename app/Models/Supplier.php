<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'tax_id', 'email', 'phone', 'notes', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}

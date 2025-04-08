<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitationItem extends Model
{
    use HasFactory;

    public $table = 'solicitation_items';
    
    protected $fillable = [
        'solicitation_id',
        'description',
        'value',
        'due_date',
    ];

    public function solicitation(): BelongsTo{
        return $this->belongsTo(related: Solicitation::class);
    }
}

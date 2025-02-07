<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    public $table = 'credit_configurations';

    protected $fillable = [
        'description',
        'start_score',
        'end_score',
        'has_law_processes',
        'has_pending_issues',
        'min_pending_value',
        'status',
    ];
}
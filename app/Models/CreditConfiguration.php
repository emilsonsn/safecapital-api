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
        'max_pending_value',
        'process_categories',
        'status',
    ];

    protected $casts = [
        'id' => 'integer',
        'description' => 'string',
        'start_score' => 'integer',
        'end_score' => 'integer',
        'has_law_processes' => 'boolean',
        'has_pending_issues' => 'boolean',
        'max_pending_value' => 'float',
        'process_categories' => 'array',
        'status' => 'string',
    ];
}
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
        'start_approved_score',
        'end_approved_score',
        'start_pending_score',
        'end_pending_score',
        'start_disapproved_score',
        'end_disapproved_score',
    ];
}

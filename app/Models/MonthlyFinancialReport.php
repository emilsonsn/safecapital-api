<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyFinancialReport extends Model
{
    protected $fillable = [
        'reference_month', 'invoice_income', 'recoveries_income', 'total_income',
        'total_expenses', 'net_balance', 'recoverable_balance', 'generated_at',
    ];

    protected $casts = [
        'reference_month' => 'date',
        'invoice_income' => 'float',
        'recoveries_income' => 'float',
        'total_income' => 'float',
        'total_expenses' => 'float',
        'net_balance' => 'float',
        'recoverable_balance' => 'float',
        'generated_at' => 'datetime',
    ];
}

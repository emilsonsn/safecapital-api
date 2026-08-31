<?php

namespace App\Enums;

enum ExpenseStatusEnum: string
{
    case Pending = 'PENDING';
    case Paid = 'PAID';
    case Cancelled = 'CANCELLED';
}

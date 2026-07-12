<?php

namespace App\Enums;

enum InvoiceStatusEnum: string
{
    case Open = 'OPEN';
    case Paid = 'PAID';
    case Overdue = 'OVERDUE';
    case Cancelled = 'CANCELLED';
}

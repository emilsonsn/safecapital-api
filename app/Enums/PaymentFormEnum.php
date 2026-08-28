<?php

namespace App\Enums;

enum PaymentFormEnum: string
{
    case InCash = 'INCASH';
    case Invoiced = 'INVOICED';

    public function label(): string
    {
        return match ($this) {
            self::InCash => 'À Vista',
            self::Invoiced => 'Parcelado (12x)',
        };
    }

    public function installments(): int
    {
        return match ($this) {
            self::InCash => 1,
            self::Invoiced => 12,
        };
    }
}
<?php

namespace App\Enums;

enum InstallmentStatusEnum: string
{
    case Open = 'OPEN';
    case BoletoSent = 'BOLETO_SENT';
    case Paid = 'PAID';
    case Removed = 'REMOVED';
    case Overdue = 'OVERDUE';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Em aberto',
            self::BoletoSent => 'Boleto enviado (aguardando validação)',
            self::Paid => 'Pago',
            self::Removed => 'Removido',
            self::Overdue => 'Vencido',
            self::Cancelled => 'Cancelado',
        };
    }
}
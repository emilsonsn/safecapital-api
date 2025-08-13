<?php

namespace App\Traits;

use App\Enums\ClientStatusEnum;
use App\Models\ClientAnalisy;

trait BlocksCpfByRecentDisapproval
{
    private function hasRecentDisapproval(string $cpf, int $months = 6): bool
    {
        return ClientAnalisy::where('cpf', $cpf)
            ->where('status', ClientStatusEnum::Disapproved->value)
            ->where('created_at', '>=', now()->subMonths($months))
            ->exists();
    }
}

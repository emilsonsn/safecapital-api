<?php

namespace App\Helpers;

use App\Models\CreditConfiguration;
use App\Models\User;

class Helpers {
    public static function getAdminAndManagerUsers(): array {
        return User::whereIn('role', [
            'admin',
            'manager'])
        ->get()
        ->all();
    }

    public static function getCreditSettings(): array{
        return CreditConfiguration::get()->all();
    }
}

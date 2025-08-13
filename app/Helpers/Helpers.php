<?php

namespace App\Helpers;

use App\Models\CreditConfiguration;
use App\Models\TaxSetting;
use App\Models\User;

class Helpers {
    public static function getAdminAndManagerUsers(): array {
        $AdminsAndmanagers = User::whereIn('role', [
            'admin',
            'manager'])
        ->get()
        ->all();

        return $AdminsAndmanagers ?? [];
    }

    public static function getCreditSettings(): array
    {
        return CreditConfiguration::orderByRaw("
            CASE status
                WHEN 'approved' THEN 1
                WHEN 'pending' THEN 2
                WHEN 'disapproved' THEN 3
                ELSE 4
            END
        ")->get()->all();
    }

    public static function getTaxSettings(): TaxSetting{
        return TaxSetting::first();
    }
}

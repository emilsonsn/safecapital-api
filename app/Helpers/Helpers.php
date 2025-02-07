<?php

namespace App\Helpers;

use App\Models\User;

class Helpers {
    public static function getAdminAndManagerUsers(): array {
        return User::whereIn('role', [
            'admin',
            'manager'])
        ->get()
        ->all();
    }
}

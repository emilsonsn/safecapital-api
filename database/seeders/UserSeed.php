<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        User::firstOrCreate([
            'email' => 'admin@admin',
        ],
        [
            'name' => 'admin',
            'surname' => 'do sistema',
            'phone' => '83991236636',
            'company_name' => 'TechSoul',
            'email' => 'admin@admin',
            'password' => Hash::make('admin'),
            'cnpj' => '50774377000176',
            'creci' => null,
            'is_active' => true,
            'validation' => null,
            'role' => 'Admin',
        ]);
    }
}

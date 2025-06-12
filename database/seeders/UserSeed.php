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
        User::updateOrCreate([
            'email' => 'safecapitalgarantias@gmail.com',
        ],
        [
            'name' => 'Safe Capital',
            'surname' => 'Garantias',
            'phone' => '',
            'company_name' => '',
            'password' => Hash::make('@123Mudar'),
            'cnpj' => '53647890000194',
            'creci' => null,
            'is_active' => true,
            'validation' => null,
            'role' => 'Admin',
        ]);
    }
}

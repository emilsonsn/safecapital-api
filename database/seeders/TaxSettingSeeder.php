<?php

namespace Database\Seeders;

use App\Models\TaxSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaxSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TaxSetting::create([
            'percentage' => 10,
            'tax' => 150,
        ]);
    }
}

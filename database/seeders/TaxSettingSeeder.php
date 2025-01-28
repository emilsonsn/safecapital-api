<?php

namespace Database\Seeders;

use App\Models\TaxSetting;
use Illuminate\Database\Seeder;

class TaxSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        TaxSetting::where('id', '>', 0)->delete();

        $tax = [
            'percentage' => 10,
            'tax' => 150,
        ];

        TaxSetting::firstOrcreate($tax, $tax);
    }
}
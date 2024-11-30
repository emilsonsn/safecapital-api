<?php

namespace Database\Seeders;

use App\Models\CreditConfiguration;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreditConfigurationSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configuration = [
            'start_approved_score' => 620,
            'end_approved_score' => 1000,
            'start_pending_score' => 400,
            'end_pending_score' => 620,
            'start_disapproved_score' => 0,
            'end_disapproved_score' => 400,
        ];
        
        CreditConfiguration::firstOrcreate($configuration, $configuration);
    }
}

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
        $configurations = [
            [
                'description' => 'Aprovado',
                'start_score' => 601,
                'end_score' => 1000,
                'has_pending_issues' => true,
                'status' => 'Approved',
            ],
            [
                'description' => 'Aprovado',
                'start_score' => 601,
                'end_score' => 1000,
                'has_pending_issues' => true,
                'status' => 'Pending',
            ],            
        ];

        foreach ($configurations as $configuration) {
            CreditConfiguration::firstOrcreate($configuration, $configuration);
        }
    }
}

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
                'has_law_processes' => false,
                'min_pending_value' => 100,
                'status' => 'Approved',
            ],
            [
                'description' => 'Pendente',
                'start_score' => 401,
                'end_score' => 600,
                'has_law_processes' => false,
                'min_pending_value' => 100,
                'has_pending_issues' => true,
                'status' => 'Pending',
            ],            
            [
                'description' => 'Reprovado - Apenas score',
                'start_score' => 400,
                'end_score' => 0,
                'has_law_processes' => false,
                'min_pending_value' => 100,
                'has_pending_issues' => false,
                'status' => 'Disapproved',
            ],
            [
                'description' => 'Reprovado -  Score e pendências',
                'start_score' => 400,
                'end_score' => 0,
                'has_law_processes' => false,
                'min_pending_value' => 100,
                'has_pending_issues' => true,
                'status' => 'Disapproved',
            ],
        ];

        foreach ($configurations as $configuration) {
            CreditConfiguration::firstOrcreate($configuration, $configuration);
        }
    }
}

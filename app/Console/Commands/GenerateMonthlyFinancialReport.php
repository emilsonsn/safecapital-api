<?php

namespace App\Console\Commands;

use App\Services\Finance\CashflowService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlyFinancialReport extends Command
{
    protected $signature = 'finance:generate-monthly-report {--month=}';

    protected $description = 'Gera ou atualiza o balancete financeiro mensal';

    public function handle(CashflowService $service): int
    {
        $month = $this->option('month')
            ? Carbon::createFromFormat('Y-m', $this->option('month'))
            : now()->subMonthNoOverflow();
        $report = $service->generateMonthlyReport($month);

        $this->info("Balancete {$report->reference_month->format('Y-m')} gerado.");

        return self::SUCCESS;
    }
}

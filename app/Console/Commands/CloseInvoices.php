<?php

namespace App\Console\Commands;

use App\Services\Finance\InvoiceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CloseInvoices extends Command
{
    protected $signature = 'finance:close-invoices {--date=}';
    protected $description = 'Fecha e gera os boletos consolidados das faturas';

    public function handle(InvoiceService $service): int
    {
        $date = Carbon::parse($this->option('date') ?: now());
        $this->info($service->close($date).' fatura(s) gerada(s).');
        return self::SUCCESS;
    }
}

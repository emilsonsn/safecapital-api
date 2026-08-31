<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\Client\ClientInstallmentService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateUpcomingInstallments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-upcoming-installments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate upcoming installments for clients';

    /**
     * Execute the console command.
     */
    public function handle(ClientInstallmentService $service)
    {
        $today = Carbon::parse('2026-03-30');

        $clients = Client::whereHas('installments')
            ->with(['installments' => function ($q) {
                $q->orderByDesc('installment_number');
            }])
            ->get();

        foreach ($clients as $client) {
            $lastInstallment = $client->installments->first();
            $countInstallment = $client->installments->count();

            if (! $lastInstallment) {
                continue;
            }

            $totalInstallments = $client->payment_form->installments();

            if ($countInstallment >= $totalInstallments) {
                continue;
            }

            $cycleDay = $lastInstallment->due_date->day;
            $nextMonth = $lastInstallment->due_date->copy()->addMonth();

            if ($cycleDay > $nextMonth->daysInMonth) {
                $nextDueDate = $nextMonth->endOfMonth();
            } else {
                $nextDueDate = $nextMonth->day($cycleDay);
            }

            if ($today->lt($nextDueDate->copy()->subDays(7))) {
                continue;
            }
            
            $service->generateNextInstallment($client);
        }
    }
}

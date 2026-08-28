<?php

namespace App\Services\Finance;

use App\Enums\ExpenseStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\RecoverableStatusEnum;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\RecoverableValue;
use Carbon\Carbon;

class FinancialDashboardService
{
    public function __construct(
        private readonly CashflowService $cashflow,
        private readonly InvoiceService $invoices,
    ) {}

    public function dashboard(Carbon $month, int $months, int $dueDays): array
    {
        $month = $month->copy()->startOfMonth();
        $this->invoices->syncOverdueInvoices();

        $report = $this->cashflow->generateMonthlyReport($month);
        $today = now()->startOfDay();
        $dueLimit = $today->copy()->addDays($dueDays)->endOfDay();

        return [
            'filters' => [
                'month' => $month->format('Y-m'),
                'months' => $months,
                'due_days' => $dueDays,
            ],
            'summary' => [
                'invoice_income' => $report->invoice_income,
                'recoveries_income' => $report->recoveries_income,
                'total_income' => $report->total_income,
                'total_expenses' => $report->total_expenses,
                'net_balance' => $report->net_balance,
                'recoverable_balance' => $report->recoverable_balance,
            ],
            'invoices' => [
                'open' => $this->invoiceMetric(InvoiceStatusEnum::Open),
                'overdue' => $this->invoiceMetric(InvoiceStatusEnum::Overdue),
                'due_soon' => $this->invoiceDueSoon($today, $dueLimit),
            ],
            'expenses' => [
                'pending' => $this->expenseMetric(ExpenseStatusEnum::Pending),
                'due_soon' => $this->expenseDueSoon($today, $dueLimit),
            ],
            'recoverables' => [
                'pending' => $this->recoverableMetric(),
                'expected_soon' => $this->recoverableExpectedSoon($today, $dueLimit),
            ],
            'chart' => $this->monthlyChart($month, $months),
            'recent_activity' => $this->recentActivity($month),
        ];
    }

    private function invoiceMetric(InvoiceStatusEnum $status): array
    {
        $query = Invoice::query()->where('status', $status->value);

        return ['count' => $query->count(), 'amount' => (float) $query->sum('amount')];
    }

    private function invoiceDueSoon(Carbon $today, Carbon $dueLimit): array
    {
        $query = Invoice::query()
            ->where('status', InvoiceStatusEnum::Open->value)
            ->whereBetween('due_date', [$today, $dueLimit]);

        return ['count' => $query->count(), 'amount' => (float) $query->sum('amount')];
    }

    private function expenseMetric(ExpenseStatusEnum $status): array
    {
        $query = Expense::query()->where('status', $status->value);

        return ['count' => $query->count(), 'amount' => (float) $query->sum('amount')];
    }

    private function expenseDueSoon(Carbon $today, Carbon $dueLimit): array
    {
        $query = Expense::query()
            ->where('status', ExpenseStatusEnum::Pending->value)
            ->whereBetween('due_date', [$today, $dueLimit]);

        return ['count' => $query->count(), 'amount' => (float) $query->sum('amount')];
    }

    private function recoverableMetric(): array
    {
        $query = RecoverableValue::query()->where('status', RecoverableStatusEnum::Pending->value);

        return ['count' => $query->count(), 'amount' => (float) $query->sum('amount')];
    }

    private function recoverableExpectedSoon(Carbon $today, Carbon $dueLimit): array
    {
        $query = RecoverableValue::query()
            ->where('status', RecoverableStatusEnum::Pending->value)
            ->whereBetween('expected_recovery_date', [$today, $dueLimit]);

        return ['count' => $query->count(), 'amount' => (float) $query->sum('amount')];
    }

    private function monthlyChart(Carbon $month, int $months): array
    {
        return collect(range($months - 1, 0))
            ->map(function (int $offset) use ($month): array {
                $report = $this->cashflow->generateMonthlyReport($month->copy()->subMonthsNoOverflow($offset));

                return [
                    'month' => $report->reference_month->format('Y-m'),
                    'total_income' => $report->total_income,
                    'total_expenses' => $report->total_expenses,
                    'net_balance' => $report->net_balance,
                    'recoverable_balance' => $report->recoverable_balance,
                ];
            })
            ->values()
            ->all();
    }

    private function recentActivity(Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $invoiceActivities = Invoice::query()
            ->with('user:id,name,surname,company_name')
            ->where('status', InvoiceStatusEnum::Paid->value)
            ->whereBetween('paid_at', [$start, $end])
            ->latest('paid_at')->limit(10)->get()
            ->map(fn (Invoice $invoice) => [
                'type' => 'INVOICE_PAYMENT',
                'id' => $invoice->id,
                'description' => 'Fatura recebida de '.trim(($invoice->user?->name ?? 'Cliente').' '.($invoice->user?->surname ?? '')),
                'amount' => $invoice->amount,
                'occurred_at' => $invoice->paid_at,
            ]);
        $expenseActivities = Expense::query()
            ->with('supplier:id,name')
            ->where('status', ExpenseStatusEnum::Paid->value)
            ->whereBetween('paid_at', [$start, $end])
            ->latest('paid_at')->limit(10)->get()
            ->map(fn (Expense $expense) => [
                'type' => 'EXPENSE_PAYMENT',
                'id' => $expense->id,
                'description' => 'Saída paga: '.$expense->description,
                'amount' => $expense->amount,
                'occurred_at' => $expense->paid_at,
            ]);
        $recoverableActivities = RecoverableValue::query()
            ->where('status', RecoverableStatusEnum::Recovered->value)
            ->whereBetween('received_at', [$start, $end])
            ->latest('received_at')->limit(10)->get()
            ->map(fn (RecoverableValue $recoverable) => [
                'type' => 'RECOVERABLE_RECEIPT',
                'id' => $recoverable->id,
                'description' => 'Valor resgatado: '.$recoverable->description,
                'amount' => $recoverable->amount,
                'occurred_at' => $recoverable->received_at,
            ]);

        return collect()
            ->concat($invoiceActivities)
            ->concat($expenseActivities)
            ->concat($recoverableActivities)
            ->sortByDesc(fn (array $activity) => $activity['occurred_at'])
            ->take(10)
            ->values()
            ->all();
    }
}

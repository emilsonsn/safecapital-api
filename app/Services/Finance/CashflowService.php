<?php

namespace App\Services\Finance;

use App\Enums\ExpenseStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\RecoverableStatusEnum;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\MonthlyFinancialReport;
use App\Models\RecoverableValue;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CashflowService
{
    public function listSuppliers(array $filters): LengthAwarePaginator
    {
        $perPage = $this->perPage($filters);
        $search = trim((string) ($filters['search'] ?? ''));

        return Supplier::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('tax_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['is_active']), fn ($query) => $query->where('is_active', $filters['is_active']))
            ->withCount('expenses')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function createSupplier(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function updateSupplier(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier->fresh();
    }

    public function listExpenses(array $filters): LengthAwarePaginator
    {
        $perPage = $this->perPage($filters);
        $search = trim((string) ($filters['search'] ?? ''));
        $statuses = $this->statuses($filters['status'] ?? null, ExpenseStatusEnum::cases());

        return Expense::query()
            ->with(['supplier:id,name', 'createdBy:id,name,surname', 'paidBy:id,name,surname'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($statuses !== [], fn ($query) => $query->whereIn('status', $statuses))
            ->when(! empty($filters['supplier_id']), fn ($query) => $query->where('supplier_id', $filters['supplier_id']))
            ->when(! empty($filters['due_from']), fn ($query) => $query->whereDate('due_date', '>=', $filters['due_from']))
            ->when(! empty($filters['due_to']), fn ($query) => $query->whereDate('due_date', '<=', $filters['due_to']))
            ->latest('due_date')
            ->paginate($perPage);
    }

    public function createExpense(array $data, User $admin): Expense
    {
        $isPaid = ($data['status'] ?? ExpenseStatusEnum::Pending->value) === ExpenseStatusEnum::Paid->value;

        return Expense::create([
            ...$data,
            'status' => $data['status'] ?? ExpenseStatusEnum::Pending,
            'created_by_user_id' => $admin->id,
            'paid_at' => $isPaid ? ($data['paid_at'] ?? now()) : null,
            'paid_by_user_id' => $isPaid ? $admin->id : null,
        ]);
    }

    public function updateExpense(Expense $expense, array $data): Expense
    {
        if (($data['status'] ?? null) === ExpenseStatusEnum::Paid->value
            && $expense->status !== ExpenseStatusEnum::Paid) {
            throw ValidationException::withMessages([
                'status' => 'Use a ação de marcar como paga para registrar a baixa da saída.',
            ]);
        }

        if ($expense->status === ExpenseStatusEnum::Paid && array_key_exists('status', $data)
            && $data['status'] !== ExpenseStatusEnum::Paid->value) {
            throw ValidationException::withMessages([
                'status' => 'Uma saída paga não pode voltar para pendente ou cancelada.',
            ]);
        }

        $expense->update($data);

        return $expense->fresh(['supplier:id,name', 'createdBy:id,name,surname', 'paidBy:id,name,surname']);
    }

    public function markExpenseAsPaid(Expense $expense, User $admin, array $data): Expense
    {
        if ($expense->status === ExpenseStatusEnum::Cancelled) {
            throw ValidationException::withMessages([
                'expense' => 'Uma saída cancelada não pode ser marcada como paga.',
            ]);
        }

        if ($expense->status === ExpenseStatusEnum::Paid) {
            return $expense->load(['supplier:id,name', 'createdBy:id,name,surname', 'paidBy:id,name,surname']);
        }

        $expense->update([
            'status' => ExpenseStatusEnum::Paid,
            'paid_at' => isset($data['paid_at']) ? Carbon::parse($data['paid_at']) : now(),
            'paid_by_user_id' => $admin->id,
            'payment_reference' => $data['payment_reference'] ?? null,
            'notes' => $data['notes'] ?? $expense->notes,
        ]);

        return $expense->fresh(['supplier:id,name', 'createdBy:id,name,surname', 'paidBy:id,name,surname']);
    }

    public function listRecoverables(array $filters): LengthAwarePaginator
    {
        $perPage = $this->perPage($filters);
        $search = trim((string) ($filters['search'] ?? ''));
        $statuses = $this->statuses($filters['status'] ?? null, RecoverableStatusEnum::cases());

        return RecoverableValue::query()
            ->with(['createdBy:id,name,surname', 'resolvedBy:id,name,surname'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('reference', 'like', "%{$search}%")
                        ->orWhere('case_number', 'like', "%{$search}%")
                        ->orWhere('counterparty', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($statuses !== [], fn ($query) => $query->whereIn('status', $statuses))
            ->when(! empty($filters['expected_from']), fn ($query) => $query->whereDate('expected_recovery_date', '>=', $filters['expected_from']))
            ->when(! empty($filters['expected_to']), fn ($query) => $query->whereDate('expected_recovery_date', '<=', $filters['expected_to']))
            ->latest('expected_recovery_date')
            ->paginate($perPage);
    }

    public function createRecoverable(array $data, User $admin): RecoverableValue
    {
        return RecoverableValue::create([
            ...$data,
            'reference' => $data['reference'] ?? 'RES-'.Str::upper(Str::random(10)),
            'status' => RecoverableStatusEnum::Pending,
            'created_by_user_id' => $admin->id,
        ]);
    }

    public function updateRecoverable(RecoverableValue $recoverable, array $data): RecoverableValue
    {
        if ($recoverable->status !== RecoverableStatusEnum::Pending) {
            throw ValidationException::withMessages([
                'recoverable' => 'Somente valores pendentes podem ser alterados.',
            ]);
        }

        $recoverable->update($data);

        return $recoverable->fresh(['createdBy:id,name,surname', 'resolvedBy:id,name,surname']);
    }

    public function markRecoverableAsReceived(RecoverableValue $recoverable, User $admin, array $data): RecoverableValue
    {
        return $this->resolveRecoverable($recoverable, $admin, RecoverableStatusEnum::Recovered, $data);
    }

    public function markRecoverableAsLost(RecoverableValue $recoverable, User $admin, array $data): RecoverableValue
    {
        return $this->resolveRecoverable($recoverable, $admin, RecoverableStatusEnum::Lost, $data);
    }

    public function generateMonthlyReport(Carbon $month): MonthlyFinancialReport
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $invoiceIncome = (float) Invoice::query()
            ->where('status', InvoiceStatusEnum::Paid->value)
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');
        $recoveriesIncome = (float) RecoverableValue::query()
            ->where('status', RecoverableStatusEnum::Recovered->value)
            ->whereBetween('received_at', [$start, $end])
            ->sum('amount');
        $totalExpenses = (float) Expense::query()
            ->where('status', ExpenseStatusEnum::Paid->value)
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');
        $recoverableBalance = (float) RecoverableValue::query()
            ->where('created_at', '<=', $end)
            ->where(function ($query) use ($end): void {
                $query->whereNull('resolved_at')->orWhere('resolved_at', '>', $end);
            })
            ->sum('amount');
        $totalIncome = $invoiceIncome + $recoveriesIncome;

        $data = [
            'invoice_income' => $invoiceIncome,
            'recoveries_income' => $recoveriesIncome,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_balance' => $totalIncome - $totalExpenses,
            'recoverable_balance' => $recoverableBalance,
            'generated_at' => now(),
        ];
        $report = MonthlyFinancialReport::query()
            ->whereDate('reference_month', $start)
            ->first();

        if ($report) {
            $report->update($data);

            return $report->fresh();
        }

        return MonthlyFinancialReport::create([
            ...$data,
            'reference_month' => $start->toDateString(),
        ]);
    }

    public function listMonthlyReports(array $filters): LengthAwarePaginator
    {
        return MonthlyFinancialReport::query()
            ->latest('reference_month')
            ->paginate($this->perPage($filters));
    }

    private function resolveRecoverable(
        RecoverableValue $recoverable,
        User $admin,
        RecoverableStatusEnum $status,
        array $data
    ): RecoverableValue {
        if ($recoverable->status !== RecoverableStatusEnum::Pending) {
            throw ValidationException::withMessages([
                'recoverable' => 'Este valor já foi resolvido.',
            ]);
        }

        $resolvedAt = isset($data['resolved_at']) ? Carbon::parse($data['resolved_at']) : now();
        $recoverable->update([
            'status' => $status,
            'resolved_at' => $resolvedAt,
            'received_at' => $status === RecoverableStatusEnum::Recovered ? $resolvedAt : null,
            'resolved_by_user_id' => $admin->id,
            'notes' => $data['notes'] ?? $recoverable->notes,
        ]);

        return $recoverable->fresh(['createdBy:id,name,surname', 'resolvedBy:id,name,surname']);
    }

    private function perPage(array $filters): int
    {
        return min(max((int) ($filters['per_page'] ?? 15), 1), 100);
    }

    private function statuses(?string $value, array $allowed): array
    {
        $allowed = array_map(fn ($status) => $status->value, $allowed);

        return collect(explode(',', (string) $value))
            ->map(fn (string $status) => trim($status))
            ->filter(fn (string $status) => in_array($status, $allowed, true))
            ->values()
            ->all();
    }
}

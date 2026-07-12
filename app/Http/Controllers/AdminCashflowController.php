<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\RecoverableValue;
use App\Models\Supplier;
use App\Services\Finance\CashflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminCashflowController extends Controller
{
    public function suppliers(Request $request, CashflowService $service)
    {
        return response()->json([
            'status' => true,
            'data' => $service->listSuppliers($request->only(['search', 'is_active', 'per_page'])),
        ]);
    }

    public function storeSupplier(Request $request, CashflowService $service)
    {
        $supplier = $service->createSupplier($this->validateSupplier($request));

        return response()->json(['status' => true, 'data' => $supplier], 201);
    }

    public function updateSupplier(Request $request, Supplier $supplier, CashflowService $service)
    {
        return response()->json(['status' => true, 'data' => $service->updateSupplier($supplier, $this->validateSupplier($request, false))]);
    }

    public function expenses(Request $request, CashflowService $service)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'due_from' => ['nullable', 'date'],
            'due_to' => ['nullable', 'date', 'after_or_equal:due_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json(['status' => true, 'data' => $service->listExpenses($validated)]);
    }

    public function storeExpense(Request $request, CashflowService $service)
    {
        return response()->json([
            'status' => true,
            'data' => $service->createExpense($this->validateExpense($request), Auth::user()),
        ], 201);
    }

    public function updateExpense(Request $request, Expense $expense, CashflowService $service)
    {
        return response()->json([
            'status' => true,
            'data' => $service->updateExpense($expense, $this->validateExpense($request, false)),
        ]);
    }

    public function markExpenseAsPaid(Request $request, Expense $expense, CashflowService $service)
    {
        $data = $request->validate([
            'paid_at' => ['nullable', 'date', 'before_or_equal:now'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Saída marcada como paga.',
            'data' => $service->markExpenseAsPaid($expense, Auth::user(), $data),
        ]);
    }

    public function recoverables(Request $request, CashflowService $service)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string'],
            'expected_from' => ['nullable', 'date'],
            'expected_to' => ['nullable', 'date', 'after_or_equal:expected_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json(['status' => true, 'data' => $service->listRecoverables($validated)]);
    }

    public function storeRecoverable(Request $request, CashflowService $service)
    {
        return response()->json([
            'status' => true,
            'data' => $service->createRecoverable($this->validateRecoverable($request), Auth::user()),
        ], 201);
    }

    public function updateRecoverable(Request $request, RecoverableValue $recoverable, CashflowService $service)
    {
        return response()->json([
            'status' => true,
            'data' => $service->updateRecoverable($recoverable, $this->validateRecoverable($request, false)),
        ]);
    }

    public function markRecoverableAsReceived(Request $request, RecoverableValue $recoverable, CashflowService $service)
    {
        $data = $request->validate([
            'resolved_at' => ['nullable', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Valor marcado como recebido.',
            'data' => $service->markRecoverableAsReceived($recoverable, Auth::user(), $data),
        ]);
    }

    public function markRecoverableAsLost(Request $request, RecoverableValue $recoverable, CashflowService $service)
    {
        $data = $request->validate([
            'resolved_at' => ['nullable', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Valor marcado como não recuperável.',
            'data' => $service->markRecoverableAsLost($recoverable, Auth::user(), $data),
        ]);
    }

    public function monthlyReport(Request $request, CashflowService $service)
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = isset($data['month']) ? Carbon::createFromFormat('Y-m', $data['month']) : now();

        return response()->json(['status' => true, 'data' => $service->generateMonthlyReport($month)]);
    }

    public function monthlyReports(Request $request, CashflowService $service)
    {
        $data = $request->validate(['per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);

        return response()->json(['status' => true, 'data' => $service->listMonthlyReports($data)]);
    }

    private function validateSupplier(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:30', 'unique:suppliers,tax_id'.($creating ? '' : ','.$request->route('supplier')->id)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function validateExpense(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'category' => [$creating ? 'required' : 'sometimes', 'string', 'max:100'],
            'description' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'amount' => [$creating ? 'required' : 'sometimes', 'numeric', 'gt:0'],
            'due_date' => ['nullable', 'date'],
            'paid_at' => ['nullable', 'date', 'before_or_equal:now'],
            'status' => ['nullable', 'in:PENDING,PAID,CANCELLED'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function validateRecoverable(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'reference' => ['nullable', 'string', 'max:100', 'unique:recoverable_values,reference'.($creating ? '' : ','.$request->route('recoverable')->id)],
            'case_number' => ['nullable', 'string', 'max:100'],
            'counterparty' => ['nullable', 'string', 'max:255'],
            'description' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'amount' => [$creating ? 'required' : 'sometimes', 'numeric', 'gt:0'],
            'expected_recovery_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}

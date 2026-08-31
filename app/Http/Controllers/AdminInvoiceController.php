<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Finance\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminInvoiceController extends Controller
{
    public function clients(Request $request, InvoiceService $service)
    {
        return response()->json([
            'status' => true,
            'data' => $service->listClientUsers($request->only(['search', 'per_page'])),
        ]);
    }

    public function invoices(Request $request, User $user, InvoiceService $service)
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'due_from' => ['nullable', 'date'],
            'due_to' => ['nullable', 'date', 'after_or_equal:due_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json([
            'status' => true,
            'client' => $user->only(['id', 'name', 'surname', 'company_name', 'email']),
            'data' => $service->listForAdmin($user, $validated),
        ]);
    }

    public function markAsPaid(Request $request, User $user, Invoice $invoice, InvoiceService $service)
    {
        $validated = $request->validate([
            'paid_at' => ['nullable', 'date', 'before_or_equal:now'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'payment_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Fatura marcada como paga.',
            'data' => $service->markAsPaid($user, $invoice, Auth::user(), $validated),
        ]);
    }
}

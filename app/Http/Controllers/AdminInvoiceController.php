<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Finance\InvoiceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use RuntimeException;

class AdminInvoiceController extends Controller
{
    public function close(Request $request, InvoiceService $service)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        try {
            $created = $service->close(Carbon::parse($validated['date'] ?? now()));
        } catch (RuntimeException $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => "{$created} fatura(s) gerada(s).",
            'data' => ['created' => $created],
        ]);
    }

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

    public function updateStatus(Request $request, User $user, Invoice $invoice, InvoiceService $service)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['OPEN', 'OVERDUE', 'CANCELLED'])],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Status da fatura atualizado.',
            'data' => $service->updateStatus($user, $invoice, $validated['status']),
        ]);
    }

    public function uploadProof(Request $request, User $user, Invoice $invoice, InvoiceService $service)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Comprovante enviado com sucesso.',
            'data' => $service->uploadPaymentProof($user, $invoice, $validated['file']),
        ]);
    }
}

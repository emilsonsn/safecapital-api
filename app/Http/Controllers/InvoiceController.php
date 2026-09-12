<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\Finance\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function index(Request $request, InvoiceService $service)
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json(['status' => true, 'data' => $service->listForUser(Auth::user(), $validated)]);
    }

    public function uploadProof(Request $request, Invoice $invoice, InvoiceService $service)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $invoice = $service->uploadPaymentProof(Auth::user(), $invoice, $validated['file']);

        return response()->json([
            'status' => true,
            'message' => 'Comprovante enviado com sucesso.',
            'data' => $invoice,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\Finance\InvoiceService;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function index(InvoiceService $service)
    {
        return response()->json(['status' => true, 'data' => $service->listForUser(Auth::user())]);
    }
}

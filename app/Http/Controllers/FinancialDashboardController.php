<?php

namespace App\Http\Controllers;

use App\Services\Finance\FinancialDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinancialDashboardController extends Controller
{
    public function index(Request $request, FinancialDashboardService $service)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'months' => ['nullable', 'integer', 'in:3,6,12'],
            'due_days' => ['nullable', 'integer', 'in:7,15,30'],
        ]);
        $month = isset($filters['month']) ? Carbon::createFromFormat('Y-m', $filters['month']) : now();

        return response()->json([
            'status' => true,
            'data' => $service->dashboard(
                $month,
                (int) ($filters['months'] ?? 6),
                (int) ($filters['due_days'] ?? 15),
            ),
        ]);
    }
}

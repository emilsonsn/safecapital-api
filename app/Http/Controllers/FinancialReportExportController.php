<?php

namespace App\Http\Controllers;

use App\Services\Finance\FinancialReportExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinancialReportExportController extends Controller
{
    public function monthly(Request $request, FinancialReportExportService $service)
    {
        $filters = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'format' => ['required', 'in:pdf,docx'],
        ]);
        $document = $service->export(
            Carbon::createFromFormat('Y-m', $filters['month']),
            $filters['format'],
        );

        return response()->streamDownload(
            fn () => print ($document['content']),
            $document['filename'],
            ['Content-Type' => $document['content_type']],
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\TermDocument\TermDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TermDocumentController extends Controller
{
    public function __construct(private readonly TermDocumentService $termDocumentService) {}

    public function current(): JsonResponse
    {
        return $this->response($this->termDocumentService->current());
    }

    public function store(Request $request): JsonResponse
    {
        $result = $this->termDocumentService->store($request);

        if ($result['status']) {
            $result['message'] = 'Termo atualizado com sucesso';
        }

        return $this->response($result);
    }

    private function response(array $result): JsonResponse
    {
        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'] ?? null,
            'data' => $result['data'] ?? null,
            'error' => $result['error'] ?? null,
        ], $result['statusCode'] ?? 200);
    }
}

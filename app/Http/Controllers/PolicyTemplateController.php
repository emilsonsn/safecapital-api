<?php

namespace App\Http\Controllers;

use App\Services\PolicyTemplate\PolicyTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class PolicyTemplateController extends Controller
{
    public function __construct(private readonly PolicyTemplateService $policyTemplateService) {}

    public function current(): JsonResponse
    {
        return $this->response($this->policyTemplateService->current());
    }

    public function store(Request $request): JsonResponse
    {
        $result = $this->policyTemplateService->store($request);

        if ($result['status']) {
            $result['message'] = 'Template do contrato atualizado com sucesso';
        }

        return $this->response($result);
    }

    public function download(): JsonResponse|BinaryFileResponse
    {
        try {
            $template = $this->policyTemplateService->currentModel();

            return response()->download(
                $this->policyTemplateService->pathFor($template),
                $template->filename,
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
            );
        } catch (Throwable $error) {
            return $this->response([
                'status' => false,
                'error' => $error->getMessage(),
                'statusCode' => $error->getCode() === 404 ? 404 : 400,
            ]);
        }
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

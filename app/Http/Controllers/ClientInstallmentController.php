<?php

namespace App\Http\Controllers;

use App\Models\ClientInstallment;
use App\Services\Client\ClientInstallmentService;
use Illuminate\Http\Request;

class ClientInstallmentController extends Controller
{
    private $installmentService;

    public function __construct(ClientInstallmentService $installmentService)
    {
        $this->installmentService = $installmentService;
    }
    
    public function listByClient($clientId)
    {
        $result = $this->installmentService->listByClient($clientId);

        if ($result['status']) {
            $result['message'] = "Parcelas listadas com sucesso";
        }

        return $this->response($result);
    }

    public function uploadProof(Request $request, $id)
    {
        $installment = ClientInstallment::find($id);

        if (! $installment) {
            return $this->response([
                'status' => false,
                'error' => 'Parcela não encontrada',
                'statusCode' => 404
            ]);
        }

        $result = $this->installmentService->uploadPaymentProof($request, $installment);

        if ($result['status']) {
            $result['message'] = "Comprovante enviado com sucesso";
        }

        return $this->response($result);
    }

    public function markAsPaid($id)
    {
        $installment = ClientInstallment::find($id);

        if (! $installment) {
            return $this->response([
                'status' => false,
                'error' => 'Parcela não encontrada',
                'statusCode' => 404
            ]);
        }

        $result = $this->installmentService->markAsPaid($installment);

        if ($result['status']) {
            $result['message'] = "Parcela marcada como paga com sucesso";
        }

        return $this->response($result);
    }

    private function response($result)
    {
        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'] ?? null,
            'data' => $result['data'] ?? null,
            'error' => $result['error'] ?? null
        ], $result['statusCode'] ?? 200);
    }
}
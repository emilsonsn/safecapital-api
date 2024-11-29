<?php

namespace App\Http\Controllers;

use App\Services\CreditConfiguration\CreditConfigurationService;
use Illuminate\Http\Request;

class CreditConfigurationController extends Controller
{
    private $creditConfigurationService;

    public function __construct(CreditConfigurationService $creditConfigurationService) {
        $this->creditConfigurationService = $creditConfigurationService;
    }

    public function search(){
        $result = $this->creditConfigurationService->search();

        return $result;
    }

    public function update(Request $request){
        $result = $this->creditConfigurationService->update($request);

        if($result['status']) $result['message'] = "Configuração de crédito atualizada com sucesso";
        return $this->response($result);
    }

    private function response($result){
        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'] ?? null,
            'data' => $result['data'] ?? null,
            'error' => $result['error'] ?? null
        ], $result['statusCode'] ?? 200);
    }
}

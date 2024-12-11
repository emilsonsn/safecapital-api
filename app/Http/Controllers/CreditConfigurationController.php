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

    public function search(Request $request){
        $result = $this->creditConfigurationService->search($request);

        return $result;
    }

    public function create(Request $request){
        $result = $this->creditConfigurationService->create($request);

        if($result['status']) $result['message'] = "Configuração de crédito criada com sucesso";
        return $this->response($result);
    }

    public function update(Request $request, $id){
        $result = $this->creditConfigurationService->update($request, $id);

        if($result['status']) $result['message'] = "Configuração de crédito atualizada com sucesso";
        return $this->response($result);
    }

    public function delete($id){
        $result = $this->creditConfigurationService->delete($id);

        if($result['status']) $result['message'] = "Configuração de crédito deletada com sucesso";
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

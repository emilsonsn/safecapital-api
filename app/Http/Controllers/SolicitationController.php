<?php

namespace App\Http\Controllers;

use App\Services\Solicitation\SolicitationService;
use Illuminate\Http\Request;

class SolicitationController extends Controller
{
    private $solicitationService;

    public function __construct(SolicitationService $solicitationService) {
        $this->solicitationService = $solicitationService;
    }

    public function search(Request $request){
        $result = $this->solicitationService->search($request);

        return $result;
    }

    public function create(Request $request){
        $result = $this->solicitationService->create($request);

        if($result['status']) $result['message'] = "Solicitação criada com sucesso";
        return $this->response($result);
    }

    public function update(Request $request, $id){
        $result = $this->solicitationService->update($request, $id);

        if($result['status']) $result['message'] = "Solicitação atualizada com sucesso";
        return $this->response($result);
    }

    public function createMessage(Request $request){
        $result = $this->solicitationService->createMessage($request);

        if($result['status']) $result['message'] = "Mensagem enviada com sucesso";
        return $this->response($result);
    }

    public function delete($id){
        $result = $this->solicitationService->delete($id);

        if($result['status']) $result['message'] = "Solicitação Deletada com sucesso";
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

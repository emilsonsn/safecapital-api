<?php

namespace App\Http\Controllers;

use App\Services\Client\ClientService;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    private $clientService;

    public function __construct(ClientService $clientService) {
        $this->clientService = $clientService;
    }

    public function search(Request $request){
        $result = $this->clientService->search($request);

        return $result;
    }

    public function create(Request $request){
        $result = $this->clientService->create($request);

        if($result['status']) $result['message'] = "Cliente criado com sucesso";
        return $this->response($result);
    }

    public function createPolicyDocument(Request $request){
        $result = $this->clientService->createPolicyDocument($request);

        if($result['status']) $result['message'] = "Apolice salva com sucesso";
        return $this->response($result);
    }

    public function updatePolicyDocument(Request $request, $id){
        $result = $this->clientService->updatePolicyDocument($request, $id,);

        if($result['status']) $result['message'] = "Apolice salva com sucesso";
        return $this->response($result);
    }

    public function deletePolicyDocument($id){
        $result = $this->clientService->deletePolicyDocument($id);

        if($result['status']) $result['message'] = "Apolice deletada com sucesso";
        return $this->response($result);
    }
    
    public function update(Request $request, $id){
        $result = $this->clientService->update($request, $id);

        if($result['status']) $result['message'] = "Cliente atualizado com sucesso";
        return $this->response($result);
    }

    public function delete($id){
        $result = $this->clientService->delete($id);

        if($result['status']) $result['message'] = "Cliente Deletado com sucesso";
        return $this->response($result);
    }

    public function accept($id){
        $result = $this->clientService->accept($id);

        if($result['status']) $result['message'] = "Cliente aceito com sucesso";
        return $this->response($result);
    }

    public function deleteAttachment($id){
        $result = $this->clientService->deleteAttachment($id);

        if($result['status']) $result['message'] = "Anexo Deletado com sucesso";
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

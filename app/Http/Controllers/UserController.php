<?php

namespace App\Http\Controllers;

use App\Services\User\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private $userService;

    public function __construct(UserService $userService) {
        $this->userService = $userService;
    }

    public function all() {
        $result = $this->userService->all();

        return $this->response($result);
    }

    public function search(Request $request){
        $result = $this->userService->search($request);

        return $result;
    }

    public function cards(Request $request){
        $result = $this->userService->cards($request);

        return $this->response($result);
    }

    public function getUser(){
        $result = $this->userService->getUser();

        return $this->response($result);
    }

    public function create(Request $request){
        $result = $this->userService->create($request);

        if($result['status']) $result['message'] = "Usuário criado com sucesso";
        return $this->response($result);
    }

    public function update(Request $request, $id){
        $result = $this->userService->update($request, $id);

        if($result['status']) $result['message'] = "Usuário atualizado com sucesso";
        return $this->response($result);
    }


    public function validation(Request $request, $id){
        $result = $this->userService->validate($request, $id);

        if($result['status']) $result['message'] = "Usuário validado com sucesso";
        return $this->response($result);
    }

    public function deleteAttachment($id){
        $result = $this->userService->deleteAttachment($id);

        if($result['status']) $result['message'] = "Anexo Deletado com sucesso";
        return $this->response($result);
    }

    public function userBlock($id){
        $result = $this->userService->userBlock($id);

        if($result['status']) $result['message'] = "Ação realizada com sucesso";
        return $this->response($result);
    }

    public function passwordRecovery(Request $request){
        $result = $this->userService->requestRecoverPassword($request);

        if($result['status']) $result['message'] = "Email de recuperação enviado com sucesso";
        return $this->response($result);
    }

    public function updatePassword(Request $request){
        $result = $this->userService->updatePassword($request);
        if($result['status']) $result['message'] = "Senha atualizada com sucesso";
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

<?php

namespace App\Http\Controllers;

use App\Services\TaxSetting\TaxSettingService;
use Illuminate\Http\Request;

class TaxSettingController extends Controller
{
    private $taxSettingService;

    public function __construct(TaxSettingService $taxSettingService) {
        $this->taxSettingService = $taxSettingService;
    }

    public function search(){
        $result = $this->taxSettingService->search();

        return $result;
    }
    public function update(Request $request){
        $result = $this->taxSettingService->update($request);

        if($result['status']) $result['message'] = "Configuração de taxa atualizada com sucesso";
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

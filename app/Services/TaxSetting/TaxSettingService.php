<?php

namespace App\Services\TaxSetting;

use App\Models\TaxSetting;
use Exception;
use Illuminate\Support\Facades\Validator;

class TaxSettingService
{

    public function search()
    {
        try {
            $taxSetting = TaxSetting::first();

            if(!isset($taxSetting)) {
                throw new Exception('Configuração de taxa não encontrada');
            }

            return $taxSetting;
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function update($request)
    {
        try {
            $rules = [
                'percentage' => ['required', 'numeric'],
                'tax' => ['required', 'numeric'],
            ];     

            $requestData = $request->all();

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            $taxSettingToUpdate = TaxSetting::first();

            if(!isset($taxSettingToUpdate)) {
                throw new Exception('Configuração de taxa não encontrada');
            }

            $taxSettingToUpdate->update($requestData);

            return ['status' => true, 'data' => $taxSettingToUpdate];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

}
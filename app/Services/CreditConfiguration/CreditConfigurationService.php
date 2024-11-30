<?php

namespace App\Services\CreditConfiguration;

use App\Models\CreditConfiguration;
use Exception;
use Illuminate\Support\Facades\Validator;

class CreditConfigurationService
{

    public function search()
    {
        try {
            $creditConfigurations = CreditConfiguration::first();

            if(!isset($creditConfigurations)){
                throw new Exception('Configuração de crédito não encontrada', 400);
            }

            return $creditConfigurations;
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function update($request)
    {
        try {
            $rules = [
                'start_approved_score' => ['required', 'integer'],
                'end_approved_score' => ['required', 'integer'],
                'start_pending_score' => ['required', 'integer'],
                'end_pending_score' => ['required', 'integer'],
                'start_disapproved_score' => ['required', 'integer'],
                'end_disapproved_score' => ['required', 'integer'],
            ];     

            $requestData = $request->all();

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            $creditConfigurationToUpdate = CreditConfiguration::first();

            if(!isset($creditConfigurationToUpdate)) {
                throw new Exception('Configuração de crédito não encontrada');
            }

            $creditConfigurationToUpdate->update($requestData);

            return ['status' => true, 'data' => $creditConfigurationToUpdate];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }
}
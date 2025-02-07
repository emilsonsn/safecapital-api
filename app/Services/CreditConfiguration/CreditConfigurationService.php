<?php

namespace App\Services\CreditConfiguration;

use App\Models\CreditConfiguration;
use Exception;
use Illuminate\Support\Facades\Validator;

class CreditConfigurationService
{

    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);
            $search_term = $request->search_term;
            $status = $request->status;

            $creditConfigurations = CreditConfiguration::query();

            if(isset($search_term)){
                $creditConfigurations->where('description', 'LIKE', "%$search_term%");
            }

            if(isset($status)){
                $creditConfigurations->where('status', $status);
            }

            if(!isset($creditConfigurations)){
                throw new Exception('Configuração de crédito não encontrada', 400);
            }

            $creditConfigurations = $creditConfigurations->paginate($perPage);

            return $creditConfigurations;
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function create($request)
    {
        try {
            $rules = [
                'description' => ['required', 'string', 'max:255'],
                'start_score' => ['required', 'integer'],
                'end_score' => ['required', 'integer'],
                'has_pending_issues' => ['required', 'boolean'],
                'has_law_processes' => ['required', 'boolean'],
                'min_pending_value' => ['nullable', 'numeric'],
                'status' => ['required', 'in:Pending,Approved,Disapproved'],
            ];     

            $requestData = $request->all();

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            $creditConfiguration = CreditConfiguration::create($requestData);

            return ['status' => true, 'data' => $creditConfiguration];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function update($request, $id)
    {
        try {
            $rules = [
                'description' => ['required', 'string', 'max:255'],
                'start_score' => ['required', 'integer'],
                'end_score' => ['required', 'integer'],
                'has_law_processes' => ['required', 'boolean'],
                'has_pending_issues' => ['required', 'boolean'],
                'min_pending_value' => ['nullable', 'numeric'],
                'status' => ['required', 'in:Pending,Approved,Disapproved'],
            ];     

            $requestData = $request->all();

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            $creditConfigurationToUpdate = CreditConfiguration::find($id);

            if(!isset($creditConfigurationToUpdate)) {
                throw new Exception('Configuração de crédito não encontrada');
            }

            $creditConfigurationToUpdate->update($requestData);

            return ['status' => true, 'data' => $creditConfigurationToUpdate];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function delete($id){
        try{
            $creditConfiguration = CreditConfiguration::find($id);

            if(!$creditConfiguration) throw new Exception('Configuração de crédito não encontrada');

            $configurationDescription = $creditConfiguration->description;
            $creditConfiguration->delete();

            return ['status' => true, 'data' => $configurationDescription];
        }catch(Exception $error) {
            return [ 'status' => false, 'error' => $error->getMessage(), 'statusCode' => 400 ];
        }
    }
}
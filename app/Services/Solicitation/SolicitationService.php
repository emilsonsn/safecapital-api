<?php

namespace App\Services\Solicitation;

use App\Models\Solicitation;
use App\Models\SolicitationMessage;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SolicitationService
{

    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);
            $search_term = $request->search_term;
            $status = $request->status;
            $category = $request->category;

            $solicitations = Solicitation::with('messages', 'user')
                ->orderBy('id', 'desc');

            if(isset($search_term)){
                $solicitations->where('contract_number', 'LIKE', "%{$search_term}%")
                    ->orWhere('subject', 'LIKE', "%{$search_term}%");
            }

            if(isset($status)){
                $solicitations->where('status', $status);
            }

            if(isset($category)){
                $solicitations->where('category', $category);
            }

            $solicitations = $solicitations->paginate($perPage);

            return $solicitations;
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function getById($id)
    {
        try {            

            $solicitation = Solicitation::with('messages.user')
                ->find($id);

            return ['status' => 200, 'data' => $solicitation];
        }
        catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function create($request)
    {
        try {
            $rules = [
                'contract_number' => ['required', 'string', 'max:255'],
                'subject' => ['required', 'string', 'max:255'],
                'status' => ['required', 'string', 'in:Received,UnderAnalysis,Awaiting,PaymentProvisioned,Completed'],
                'category' => ['required', 'string'],
            ];
            
            $userId = Auth::user()->id;

            $requestData = $request->all();

            $requestData['user_id'] = $userId;

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            $solicitation = Solicitation::create($requestData);

            return ['status' => true, 'data' => $solicitation];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function createMessage($request)
    {
        try {
            $rules = [  
                'message' => ['nullable', 'string', 'max:1000'],
                'attachment' => ['nullable', 'file', 'max:2048'],
                'solicitation_id' => ['required', 'integer'],
            ];

            $userId = Auth::user()->id;

            $requestData = $request->all();

            $requestData['user_id'] = $userId;

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            if(!isset($request->message) && !isset($request->message)){
                throw new Exception('Nenhum campo de mensagem ou anexo foi enviado');
            }

            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store('attachments', 'public');
                $requestData['attachment'] = $path;
            }

            $solicitation = SolicitationMessage::create($requestData);

            return ['status' => true, 'data' => $solicitation];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function update($request, $user_id)
    {
        try {
            $rules = [
                'contract_number' => ['required', 'string', 'max:255'],
                'subject' => ['required', 'string', 'max:255'],
                'status' => ['required', 'string', 'in:Received,UnderAnalysis,Awaiting,PaymentProvisioned,Completed'],
                'category' => ['required', 'string'],
            ];

            $requestData = $request->all();

            $requestData['user_id'] = Auth::user()->id;

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) throw new Exception($validator->errors());

            $solicitationToUpdate = Solicitation::find($user_id);

            if(!isset($solicitationToUpdate)) throw new Exception('Solicitação não encontrada');

            $solicitationToUpdate->update($validator->validated());

            return ['status' => true, 'data' => $solicitationToUpdate];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function delete($id){
        try{
            $solicitation = Solicitation::find($id);

            if(!$solicitation) throw new Exception('Solicitação não encontrada');

            $solicitationContractNumber = $solicitation->contract_number;
            $solicitation->delete();

            return [ 'status' => true, 'data' => $solicitationContractNumber ];
        }catch(Exception $error) {
            return [ 'status' => false, 'error' => $error->getMessage(), 'statusCode' => 400 ];
        }
    }
}
<?php

namespace App\Services\Client;

use App\Models\Client;
use App\Models\ClientAttachment;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ClientService
{

    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);
            $search_term = $request->search_term;

            $clients = Client::orderBy('id', 'desc');

            if(isset($search_term)){
                $clients->where('name', 'LIKE', "%{$search_term}%")
                    ->orWhere('surname', 'LIKE', "%{$search_term}%")
                    ->orWhere('cpf', 'LIKE', "%{$search_term}%")
                    ->orWhere('email', 'LIKE', "%{$search_term}%")
                    ->orWhere('phone', 'LIKE', "%{$search_term}%");
            }

            $clients = $clients->paginate($perPage);

            return $clients;
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function create($request)
    {
        try {
            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'surname' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:255'],
                'birthday' => ['required', 'date'],
                'cpf' => ['required', 'string', 'max:255'],
                'cep' => ['required', 'string', 'max:255'],
                'street' => ['required', 'string', 'max:255'],
                'neighborhood' => ['required', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:255'],
                'state' => ['required', 'string', 'max:255'],
                'attachments' => ['nullable', 'array'],
            ];
            $userId = Auth::user()->id;

            $requestData = $request->all();

            $requestData['user_id'] = $userId;

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                return ['status' => false, 'error' => $validator->errors(), 'statusCode' => 400];;
            }

            $client = Client::create($validator->validated());

            if($request->filled('attachments')){
                foreach($request->attachments as $attachment){
                    $path = $attachment['file']->store('attachments');
                    ClientAttachment::firstOrCreate([
                        'id' => $attachment['id'] ?? null,
                    ], [
                        'category' => $attachment['category'],
                        'filename' => $attachment['file']->getClientOriginalName(),
                        'path' => $path,
                        'client_id' => $client->id,
                    ]);
                }
            }

            return ['status' => true, 'data' => $client];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function update($request, $user_id)
    {
        try {
            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'surname' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:255'],
                'birthday' => ['required', 'date'],
                'cpf' => ['required', 'string', 'max:255'],
                'cep' => ['required', 'string', 'max:255'],
                'street' => ['required', 'string', 'max:255'],
                'neighborhood' => ['required', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:255'],
                'state' => ['required', 'string', 'max:255'],
                'attachments' => ['nullable', 'array'],             
            ];            

            $requestData = $request->all();

            $requestData['user_id'] = Auth::user()->id;

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) throw new Exception($validator->errors());

            $clientToUpdate = Client::find($user_id);

            if(!isset($clientToUpdate)) throw new Exception('Cliente não encontrado');

            $clientToUpdate->update($validator->validated());

            if($request->filled('attachments')){
                foreach($request->attachments as $attachment){
                    $path = $attachment['file']->store('attachments');
                    ClientAttachment::firstOrCreate([
                        'id' => $attachment['id'] ?? null,
                    ], [
                        'category' => $attachment['category'],
                        'filename' => $attachment['file']->getClientOriginalName(),
                        'path' => $path,
                        'client_id' => $clientToUpdate->id,
                    ]);
                }
            }

            return ['status' => true, 'data' => $clientToUpdate];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function delete($id){
        try{
            $client = Client::find($id);

            if(!$client) throw new Exception('Cliente não encontrado');

            $clientName = $client->name;
            $client->delete();

            return ['status' => true, 'data' => $clientName];
        }catch(Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function deleteAttachment($id){
        try{
            $clientAttachment = ClientAttachment::find($id);

            if(!$clientAttachment) throw new Exception('Cliente não encontrado');

            $attachmentName = $clientAttachment->filename;
            $clientAttachment->delete();

            return ['status' => true, 'data' => $attachmentName];
        }catch(Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }
}
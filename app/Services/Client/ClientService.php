<?php

namespace App\Services\Client;

use App\Models\Client;
use App\Models\ClientAttachment;
use App\Models\PolicyDocument;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ClientService
{

    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);
            $search_term = $request->search_term;
            $status = $request->status;
            $status = $request->user_id;

            $clients = Client::with('attachments')
                ->orderBy('id', 'desc');

            if(isset($search_term)){
                $clients->where(function($query) use ($search_term){
                    $query->where('name', 'LIKE', "%{$search_term}%")
                        ->orWhere('surname', 'LIKE', "%{$search_term}%")
                        ->orWhere('cpf', 'LIKE', "%{$search_term}%")
                        ->orWhere('email', 'LIKE', "%{$search_term}%")
                        ->orWhere('phone', 'LIKE', "%{$search_term}%");
                });                    
            }

            if(isset($status)){
                $clients->where('status', $status);
            }

            if(isset($user_id)){
                $clients->where('user_id', $user_id);
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
                'number'=> ['required', 'string', 'max:255'],
                'rental_value' => ['required', 'numeric'],
                'property_tax' => ['required', 'numeric'],
                'condominium_fee' => ['required', 'numeric'],
                'policy_value' => ['required', 'numeric'],
                'neighborhood' => ['required', 'string', 'max:255'],
                'observations' => ['nullable', 'string'],
                'payment_form' => ['required', 'string'],
                'complement' => ['nullable', 'string'],
                'city' => ['required', 'string', 'max:255'],
                'state' => ['required', 'string', 'max:255'],
                'attachments' => ['nullable', 'array'],
            ];
            $userId = Auth::user()->id;

            $requestData = $request->all();

            $requestData['user_id'] = $userId;

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            $client = Client::create($requestData);

            if($request->filled('attachments')){
                foreach($request->attachments as $attachment){
                    $path = $attachment['file']->store('attachments', 'public');
                    ClientAttachment::firstOrCreate([
                        'id' => $attachment['id'] ?? null,
                    ], [
                        'description' => $attachment['description'],
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
                'number'=> ['required', 'string', 'max:255'],
                'rental_value' => ['required', 'numeric'],
                'property_tax' => ['required', 'numeric'],
                'condominium_fee' => ['required', 'numeric'],
                'policy_value' => ['required', 'numeric'],
                'neighborhood' => ['required', 'string', 'max:255'],
                'observations' => ['nullable', 'string'],
                'payment_form' => ['required', 'string'],
                'complement' => ['nullable', 'string'],
                'city' => ['required', 'string', 'max:255'],
                'state' => ['required', 'string', 'max:255'],
                'attachments' => ['nullable', 'array'],             
            ];            

            $requestData = $request->all();

            $requestData['user_id'] = Auth::user()->id;

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            $clientToUpdate = Client::find($user_id);

            if(!isset($clientToUpdate)) throw new Exception('Cliente não encontrado');

            $clientToUpdate->update($validator->validated());

            if($request->filled('attachments')){
                foreach($request->attachments as $attachment){
                    $path = $attachment['file']->store('attachments', 'public');
                    ClientAttachment::firstOrCreate([
                        'id' => $attachment['id'] ?? null,
                    ], [
                        'description' => $attachment['description'],
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

    public function createPolicyDocument(Request $request, $id)
    {
        try {
            $rules = [
                'client_id' => ['required', 'integer'],
                'file' => ['required', 'file', 'mimes:docx,pdf'],
            ];
    
            $validator = Validator::make($request->all(), $rules);
    
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first(), 400);
            }
    
            if (!Client::where('id', $id)->exists()) {
                throw new Exception("Cliente não encontrado", 400);
            }
    
            $requestData = $request->all();
    
            if ($request->hasFile('file')) {
                $path = $request->file('file')->store('policy-documents', 'public');
                $requestData['path'] = $path;
            }
    
            $requestData['due_date'] = Carbon::now()->addYear();
    
            $policyDocument = PolicyDocument::create($requestData);
    
            return ['status' => true, 'data' => $policyDocument];
        } catch (Exception $error) {
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
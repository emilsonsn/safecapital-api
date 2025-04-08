<?php

namespace App\Services\Solicitation;

use App\Enums\UserRoleEnum;
use App\Helpers\Helpers;
use App\Mail\DefaultMail;
use App\Models\Solicitation;
use App\Models\SolicitationAttachment;
use App\Models\SolicitationItem;
use App\Models\SolicitationMessage;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Mail;

class SolicitationService
{

    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);
            $search_term = $request->search_term;
            $status = $request->status;
            $category = $request->category;            
            $user_id = $request->user_id;

            $solicitations = Solicitation::with(relations: [
                'messages',
                'user',
                'attachments',
                'items'
            ])->orderBy('id', 'desc');

            if(isset($search_term)){
                $solicitations->where(function($query) use($search_term){
                    $query->where('contract_number', 'LIKE', "%{$search_term}%")
                        ->orWhere('subject', 'LIKE', "%{$search_term}%");
               });             
            }

            if(isset($status)){
                $solicitations->where('status', $status);
            }

            if(isset($category)){
                $categories = explode(',' ,$category);
                $solicitations->whereIn('category', $categories);
            }

            if(Auth::user()->role !== UserRoleEnum::Admin->value){
                $solicitations->where('user_id', Auth::user()->id);
            }else if(isset($user_id)){            
                $solicitations->where('user_id', $user_id);
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
                'subject' => ['nullable', 'string', 'max:255'],
                'status' => ['required', 'string', 'in:Received,UnderAnalysis,Awaiting,PaymentProvisioned,Completed'],
                'category' => ['required', 'string'],
                'attachments' => ['nullable', 'array'],
                'attachments.*' => ['required', 'array'],
            ];
            
            $user = Auth::user();

            $requestData = $request->all();

            $requestData['user_id'] = $user->id;

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            $solicitation = Solicitation::create($requestData);

            if($request->filled('attachments') && count($request->attachments)){
                foreach($request->attachments as $attachment){
                    $path = $attachment['file']->store('attachments', 'public');
                    SolicitationAttachment::create( [
                        'description' => $attachment['description'],
                        'filename' => $attachment['file']->getClientOriginalName(),
                        'path' => $path,
                        'solicitation_id' => $solicitation->id,
                    ]);
                }
            }

            if($request->filled('items') && count($request->items)){
                foreach($request->items as $item){
                    SolicitationItem::updateOrcreate([
                        'id' => $item['id'] ?? ''
                    ],[
                        'solicitation_id' => $solicitation->id,
                        'description' => $item['description'],
                        'value' => $item['value'],
                        'due_date' => $item['due_date'],
                    ]);
                }
            }

            $adminAndManagers = Helpers::getAdminAndManagerUsers();

            if(count($adminAndManagers)){
                $message = "Cliente: {$user->name} criou uma novo chamado.";
                $subjetc = "Novo chamado criado";
                foreach($adminAndManagers as $adminOrManager){
                    Mail::to($adminOrManager->email)
                        ->send(new DefaultMail(
                            $adminOrManager->name,
                            $message,
                            $subjetc 
                        ));
                }
            }

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

            $user = Auth::user();

            $requestData = $request->all();

            $requestData['user_id'] = $user->id;

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            if(!isset($request->message) && !$request->hasFile('attachment')){
                throw new Exception('Nenhum campo de mensagem ou anexo foi enviado');
            }

            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store('attachments', 'public');
                $requestData['attachment'] = $path;
            }

            $solicitation = SolicitationMessage::create($requestData);

            if($request->filled('items') && count($request->items)){
                foreach($request->items as $item){
                    SolicitationItem::updateOrcreate([
                        'id' => $item['id'] ?? ''
                    ],[
                        'solicitation_id' => $solicitation->id,
                        'description' => $item['description'],
                        'value' => $item['value'],
                        'due_date' => $item['due_date'],
                    ]);
                }
            } 

            if($user->role === 'Client'){
                $usersToReceiveEmail = Helpers::getAdminAndManagerUsers();
            }else{
                $usersToReceiveEmail = [ $user ];
            }

            if(count($usersToReceiveEmail)){
                $message = "{$user->name} adicionou uma mensagem em seu chamado.";
                $subjetc = "Nova mensagem adicionada no chamado";
                foreach($usersToReceiveEmail as $userToReceiveEmail){
                    Mail::to($userToReceiveEmail->email)
                        ->send(new DefaultMail(
                            $userToReceiveEmail->name,
                             $message,
                            $subjetc 
                        ));
                }
            }
            
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
                'subject' => ['nullable', 'string', 'max:255'],
                'status' => ['required', 'string', 'in:Received,UnderAnalysis,Awaiting,PaymentProvisioned,Completed'],
                'category' => ['required', 'string'],
            ];

            $requestData = $request->all();

            $requestData['user_id'] = Auth::user()->id;

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) throw new Exception($validator->errors());

            $solicitationToUpdate = Solicitation::find($user_id);

            if($request->filled('items') && count($request->items)){
                foreach($request->items as $item){
                    SolicitationItem::updateOrcreate([
                        'id' => $item['id'] ?? ''
                    ],[
                        'solicitation_id' => $solicitationToUpdate->id,
                        'description' => $item['description'],
                        'value' => $item['value'],
                        'due_date' => $item['due_date'],
                    ]);
                }
            }

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

    public function deleteItem($id){
        try{
            $item = SolicitationItem::find($id);

            if(!$item) throw new Exception('Item não encontrado');

            $itemDescription = $item->description;
            $item->delete();

            return [
                'status' => true,
                'data' => $itemDescription
            ];
        }catch(Exception $error) {
            return [
                'status' => false,
                'error' => $error->getMessage(),
                'statusCode' => 400
            ];
        }
    }    
}
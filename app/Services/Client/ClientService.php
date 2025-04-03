<?php

namespace App\Services\Client;

use App\Enums\ClientStatusEnum;
use App\Enums\PaymentStatus;
use App\Enums\UserRoleEnum;
use App\Helpers\Helpers;
use App\Mail\DefaultMail;
use App\Mail\PaymentMail;
use App\Models\Client;
use App\Models\ClientAttachment;
use App\Models\ClientPayment;
use App\Models\ClientPh3Analisy;
use App\Models\PolicyDocument;
use App\Traits\MercadoPagoTrait;
use App\Traits\PH3Trait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Log;
use Mail;
use Illuminate\Support\Str;

class ClientService
{

    use PH3Trait, MercadoPagoTrait;
    
    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);
            $search_term = $request->search_term;
            $status = $request->status;
            $user_id = $request->user_id;
            $auth = Auth::user();

            $clients = Client::with('attachments', 'policy')
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

            if(isset($user_id) && $auth->role == UserRoleEnum::Admin->value){
                $clients->where('user_id', $user_id);
            }else if ($auth->role != UserRoleEnum::Admin->value){
                $clients->where('user_id', $auth->id);
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

            $ph3Result = $this->searchClienteInPH3($client);
            $this->analizeClient($client, $ph3Result);

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
                'status' => ['required', 'string'],
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

    public function accept($id){
        try{
            $client = Client::find($id);

            if(!$client) throw new Exception('Cliente não encontrado');

            $auth = Auth::user();

            $taxSetting = Helpers::getTaxSettings();

            $payment = $this->createPayment($client, $taxSetting);

            $paymentUrl = $payment['init_point'];

            $subjetc = "Safe Capital - Taxa do seguro";

            Mail::to($client->email)
                ->send(new PaymentMail(
                    $client->name,
                    $taxSetting->tax,
                    $paymentUrl,
                    $subjetc 
                ));

            $usersToReceiveEmail = Helpers::getAdminAndManagerUsers();
            $subjetc = "Novo cliente aceito";
            $message = "Cliente {$client->name} foi aceito pelo parceiro {$auth->name}.";

            foreach($usersToReceiveEmail as $userToReceiveEmail){
                Mail::to($userToReceiveEmail->email)
                    ->send(new DefaultMail(
                        $userToReceiveEmail->name,
                         $message,
                        $subjetc 
                    ));
            }            

            $client->status = ClientStatusEnum::WaitingPayment->value;
            $client->save();

            return ['status' => true, 'data' => $client];
        }catch(Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }    

    public function createPolicyDocument(Request $request)
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

            $client = Client::with('policy')->find($request->client_id);

            if(!isset($client)){
                throw new Exception('Cliente não encontrado', 400);
            }
            
            if(isset($client->policy)){
                throw new Exception('Esse cliente já possui contrato anexado', 400);
            }
    
            $requestData = $request->all();
    
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = $file->store('policy-documents', 'public');
                $requestData['path'] = $path;
                $requestData['filename'] = $file->getClientOriginalName();
            }
            
            $requestData['due_date'] = Carbon::now()->addYear();
            $requestData['contract_number'] = Carbon::now()->format('YmdHis');
            
            $policyDocument = PolicyDocument::create($requestData);

            $client->status = ClientStatusEnum::WaitingAnalysis->value;
            $client->save();

            $auth = Auth::user();

            $usersToReceiveEmail = Helpers::getAdminAndManagerUsers();

            $message = "Contrato do cliente {$client->name} anexado pelo parceiro {$auth->name}.";
            $subjetc = "Contrato anexado";
            foreach($usersToReceiveEmail as $userToReceiveEmail){
                Mail::to($userToReceiveEmail->email)
                    ->send(new DefaultMail(
                        $userToReceiveEmail->name,
                         $message,
                        $subjetc 
                    ));
            }
    
            return ['status' => true, 'data' => $policyDocument];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function updatePolicyDocument(Request $request, $id)
    {
        try {
            $rules = [
                'contract_number' => ['required', 'string'],
            ];
    
            $validator = Validator::make($request->all(), $rules);
    
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first(), 400);
            }

            $policyDocument = PolicyDocument::findOrFail($id);
    
            $requestData = $request->all();

            $policyDocument->update($requestData);
    
            return ['status' => true, 'data' => $policyDocument];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }    

    public function deletePolicyDocument($id)
    {
        try {
            $policyDocument = PolicyDocument::findOrFail($id);
    
            $policyDocumentFileName = $policyDocument->filename;

            $policyDocument->delete();

            $client = $policyDocument->client();
            $client->status = ClientStatusEnum::WaitingContract->value;
            $client->save();
    
            return ['status' => true, 'data' => $policyDocumentFileName];
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

    private function searchClienteInPH3(Client $client){
        $cpfOrCnpj = $client->cpf;
        $this->preparePh3();
        $response = $this->searchClientForCpfOrCnpj($cpfOrCnpj);

        if(!isset($response)) return;

        ClientPh3Analisy::create([
            'client_id' => $client->id,
            'response' => json_encode($response)
        ]);

        return $response;
    }

    private function analizeClient($client, $ph3Response)
    {
        $settings = Helpers::getCreditSettings();
    
        if (!$ph3Response || !isset($ph3Response['CreditScore'])) {
            $client->status = ClientStatusEnum::Pending;
            $client->save();
            return;
        }
    
        $creditScore = $ph3Response['CreditScore']['D00'] ?? 0;
        $hasLawProcesses = $ph3Response['ProcessNumber'] ?? 0; 
        $hasPendingIssues = isset($ph3Response['Debits']) && count($ph3Response['Debits']) > 0;        
        $maxPendingValue =  $hasPendingIssues ? collect($ph3Response['Debits'])->sum('CurrentQuantity') : 0;
            
        $approvedConfig = collect($settings)->first(function ($setting) use ($creditScore, $hasLawProcesses, $hasPendingIssues, $maxPendingValue) {
            return $setting['status'] === ClientStatusEnum::Approved->value &&
                   $creditScore >= $setting['start_score'] &&
                   $creditScore <= $setting['end_score'] &&
                   ($setting['has_law_processes'] == false || $hasLawProcesses == 0) &&
                   ($setting['has_pending_issues'] == false || !$hasPendingIssues) &&
                   ($setting['max_pending_value'] === null || $maxPendingValue <= $setting['max_pending_value']);
        });
    
        if ($approvedConfig) {
            $client->status = ClientStatusEnum::Approved;
            $client->save();
            return;
        }
    
        $pendingConfig = collect($settings)->first(function ($setting) use ($creditScore, $hasLawProcesses, $hasPendingIssues, $maxPendingValue) {
            return $setting['status'] === ClientStatusEnum::Pending->value &&
                   $creditScore >= $setting['start_score'] &&
                   $creditScore <= $setting['end_score'] &&
                   ($setting['has_law_processes'] == false || $hasLawProcesses == 0) &&
                   ($setting['has_pending_issues'] == false || !$hasPendingIssues) &&
                   ($setting['max_pending_value'] === null || $maxPendingValue <= $setting['max_pending_value']);
        });
    
        if ($pendingConfig) {
            $client->status = ClientStatusEnum::Pending;
            $client->save();
            return;
        }
    
        $client->status = ClientStatusEnum::Disapproved;
        $client->save();
    }

    private function createPayment($client, $taxSetting){

        $this->prepareMercadoPago(
            $client->email,
            $taxSetting->tax
        );

        $externalReference = (string) Str::uuid();
        $payment = $this->makePayment(externalReference: $externalReference);

        if(!isset($payment['init_point'])){
            Log::error('Erro no pagamento', $payment);
            throw new Exception('Falha ao gerar pagamento');
        }

        ClientPayment::create(attributes: [
            'preference_id' => $payment['id'],
            'external_id' => $externalReference,
            'client_id' => $client->id,
            'status' => PaymentStatus::Pending->value,
            'url' => $payment['init_point'],
            'transaction_amount' => $payment['items'][0]['unit_price'] ?? 0,
            'payer' => json_encode($payment['payer'] ?? []),
        ]);

        return $payment;
    }
}
<?php

namespace App\Services\Client;

use App\Enums\ClientStatusEnum;
use App\Enums\PaymentStatus;
use App\Enums\UserRoleEnum;
use App\Enums\UserValidationEnum;
use App\Helpers\Helpers;
use App\Mail\AnalisyContractMail;
use App\Mail\DefaultMail;
use App\Mail\PaymentMail;
use App\Models\Client;
use App\Models\ClientAttachment;
use App\Models\ClientPayment;
use App\Models\ClientPh3Analisy;
use App\Models\Corresponding;
use App\Models\PolicyDocument;
use App\Traits\ApiaryTrait;
use App\Traits\BlocksCpfByRecentDisapproval;
use App\Traits\Doc4SignTrait;
use App\Traits\MercadoPagoTrait;
use App\Traits\PH3Trait;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Log;
use Mail;
use PhpOffice\PhpWord\TemplateProcessor;
use App\Models\ClientAnalisy;

class ClientService
{
    use ApiaryTrait;
    use Doc4SignTrait;
    use MercadoPagoTrait;
    use PH3Trait;
    use BlocksCpfByRecentDisapproval;

    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);
            $search_term = $request->search_term;
            $status = $request->status;
            $user_id = $request->user_id;
            $auth = Auth::user();

            $clients = Client::with('attachments', 'policys', 'corresponding')
                ->orderBy('id', 'desc');

            if (isset($search_term)) {
                $clients->where(function ($query) use ($search_term) {
                    $query->where('name', 'LIKE', "%{$search_term}%")
                        ->orWhere('surname', 'LIKE', "%{$search_term}%")
                        ->orWhere('cpf', 'LIKE', "%{$search_term}%")
                        ->orWhere('email', 'LIKE', "%{$search_term}%")
                        ->orWhere('phone', 'LIKE', "%{$search_term}%");
                });
            }

            if (isset($status)) {
                $clients->where('status', $status);
            }

            if (isset($user_id) && $auth->role == UserRoleEnum::Admin->value) {
                $clients->where('user_id', $user_id);
            } elseif ($auth->role != UserRoleEnum::Admin->value) {
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
                'number' => ['required', 'string', 'max:255'],
                'property_type' => ['required', 'string', 'max:255'],
                'rental_value' => ['required', 'numeric'],
                'property_tax' => ['nullable', 'numeric'],
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

            $currentUser = Auth::user();

            $userId = $currentUser->id;

            $requestData = $request->all();

            $requestData['user_id'] = $userId;

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            if (! $currentUser->isAdmin() && $this->hasRecentDisapproval($requestData['cpf'])) {
                throw new Exception('CPF com reprovação em análise nos últimos 6 meses', 422);
            }

            DB::beginTransaction();

            $client = Client::create($requestData);

            if ($request->filled('attachments')) {
                foreach ($request->attachments as $attachment) {
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

            if ($request->corresponding && isset($request->corresponding['cpf'])) {
                $dataCorresponding = $request->corresponding;
                $corresponding = Corresponding::updateOrCreate([
                    'id' => $dataCorresponding['cpf'] ?? '',
                ], [
                    'client_id' => $client->id,
                    'cpf' => $dataCorresponding['cpf'],
                    'fullname' => $dataCorresponding['fullname'],
                    'birthday' => $dataCorresponding['birthday'],
                    'email' => $dataCorresponding['email'],
                    'phone' => $dataCorresponding['phone'],
                ]);

                $client['corresponding'] = $corresponding;
            }

            $hascorresponding = $client['corresponding'];

            if (
                ! $currentUser->isAdmin() &&
                $client->rental_value >= 1000 &&
                $client->rental_value <= 9000 &&
                $client->sumValue() <= 12000
            ) {
                $result = $this->searchCustomerCreditHistory($client);

                if (isset($result['suite']['erro']) && $result['suite']['erro']) {
                    throw new Exception('Erro ao consultar histórico de crédito do cliente. Por favor, tente novamente.');
                }

                $this->analizeClient($client, $result);

                if ($client->status != ClientStatusEnum::Approved && $client->corresponding) {
                    $result = $this->searchCustomerCreditHistory($client, true);

                    if (isset($result['suite']['erro']) && $result['suite']['erro']) {
                        throw new Exception('Erro ao consultar histórico de crédito do cliente. Por favor, tente novamente.');
                    }
                    $this->analizeClient($client, $result, true);
                }
            }

            if ($client->status == ClientStatusEnum::Pending) {
                $auth = Auth::user();

                $usersToReceiveEmail = Helpers::getAdminAndManagerUsers();
                $message = "Novo cliente pendente: {$client->name} ({$client->id}) adicionado pelo parceiro {$auth->name}.";
                $subjetc = 'Novo cliente pendente aguardando análise';

                foreach ($usersToReceiveEmail as $userToReceiveEmail) {
                    Mail::to($userToReceiveEmail->email)
                        ->send(new DefaultMail(
                            $userToReceiveEmail->name,
                            $message,
                            $subjetc
                        ));
                }
            }

            if(isset($result)){
                [$score, $hasPendings, $hasProcesses] = $this->extractAnalisyFields($result);
    
                ClientAnalisy::create(                    
                    [
                        'user_id' => $client->user_id,
                        'cpf' => $hascorresponding ? $client->corresponding->cpf : $client->cpf,
                        'score' => (string) $score,
                        'has_pendings' => $hasPendings,
                        'has_processes' => $hasProcesses,
                        'status' => ClientStatusEnum::from($client->status->value ?? $client->status),
                    ]
                );
            }

            DB::commit();
            return ['status' => true, 'data' => $client];
        } catch (Exception $error) {
            DB::rollBack();

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
                'number' => ['required', 'string', 'max:255'],
                'property_type' => ['required', 'string', 'max:255'],
                'rental_value' => ['required', 'numeric'],
                'status' => ['required', 'string'],
                'property_tax' => ['nullable', 'numeric'],
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

            $currentUser = Auth::user();

            $requestData = $request->all();

            $requestData['user_id'] = $currentUser->id;

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            if (! $currentUser->isAdmin() && $this->hasRecentDisapproval($requestData['cpf'])) {
                throw new Exception('CPF com reprovação nos últimos 6 meses', 422);
            }

            $clientToUpdate = Client::find($user_id);

            if (! isset($clientToUpdate)) {
                throw new Exception('Cliente não encontrado');
            }

            DB::beginTransaction();

            $changedCpf = ! ($clientToUpdate->cpf === $requestData['cpf']);

            $clientToUpdate->update($validator->validated());

            if ($request->filled('attachments')) {
                foreach ($request->attachments as $attachment) {
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

            if ($request->corresponding && isset($request->corresponding['cpf'])) {
                $dataCorresponding = $request->corresponding;
                $corresponding = Corresponding::updateOrCreate([
                    'id' => $dataCorresponding['cpf'] ?? '',
                ], [
                    'client_id' => $clientToUpdate->id,
                    'cpf' => $dataCorresponding['cpf'],
                    'fullname' => $dataCorresponding['fullname'],
                    'birthday' => $dataCorresponding['birthday'],
                    'email' => $dataCorresponding['email'],
                    'phone' => $dataCorresponding['phone'],
                ]);

                $clientToUpdate['corresponding'] = $corresponding;
            }

            $oldStatus = $clientToUpdate->status;

            $hascorresponding = $clientToUpdate['corresponding'];

            if (
                ! $currentUser->isAdmin() &&
                $clientToUpdate->rental_value >= 1000 &&
                $clientToUpdate->rental_value <= 9000 &&
                $clientToUpdate->sumValue() <= 12000 &&
                $changedCpf
            ) {
                $result = $this->searchCustomerCreditHistory($clientToUpdate);

                if (isset($result[0]['suite']['erro']) && $result[0]['suite']['erro'] == 'true') {
                    throw new Exception('Erro ao consultar histórico de crédito do cliente. Por favor, tente novamente.');
                }

                $this->analizeClient($clientToUpdate, $result);

                if ($clientToUpdate->status != ClientStatusEnum::Approved && $clientToUpdate->corresponding) {
                    $result = $this->searchCustomerCreditHistory($clientToUpdate, true);

                    if (isset($result['suite']['erro']) && $result['suite']['erro']) {
                        throw new Exception('Erro ao consultar histórico de crédito do cliente. Por favor, tente novamente.');
                    }
                    $this->analizeClient($clientToUpdate, $result, true);
                }
            }

            if (! $currentUser->isAdmin() &&
                $clientToUpdate->status == ClientStatusEnum::Pending &&
                $oldStatus != ClientStatusEnum::Pending
            ) {
                $auth = Auth::user();

                $usersToReceiveEmail = Helpers::getAdminAndManagerUsers();
                $message = "Novo cliente pendente: {$clientToUpdate->name} ({$clientToUpdate->id}) adicionado pelo parceiro {$auth->name}.";
                $subjetc = 'Novo cliente pendente aguardando análise';

                foreach ($usersToReceiveEmail as $userToReceiveEmail) {
                    Mail::to($userToReceiveEmail->email)
                        ->send(new DefaultMail(
                            $userToReceiveEmail->name,
                            $message,
                            $subjetc
                        ));
                }
            }

            if(isset($result)){
                [$score, $hasPendings, $hasProcesses] = $this->extractAnalisyFields($result);

                ClientAnalisy::create(
                    [
                        'user_id' => $clientToUpdate->user_id,
                        'cpf' => $hascorresponding ? $clientToUpdate->corresponding->cpf : $clientToUpdate->cpf,
                        'score' => (string) $score,
                        'has_pendings' => $hasPendings,
                        'has_processes' => $hasProcesses,
                        'status' => ClientStatusEnum::from($clientToUpdate->status->value ?? $clientToUpdate->status),
                    ]
                );
            }

            DB::commit();

            return ['status' => true, 'data' => $clientToUpdate];
        } catch (Exception $error) {
            DB::rollBack();

            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function delete($id)
    {
        try {
            $client = Client::find($id);

            if (! $client) {
                throw new Exception('Cliente não encontrado');
            }

            $clientName = $client->name;
            $client->delete();

            return ['status' => true, 'data' => $clientName];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function accept($id)
    {
        try {
            $client = Client::find($id);

            if (! $client) {
                throw new Exception('Cliente não encontrado');
            }

            $auth = Auth::user();

            $taxSetting = Helpers::getTaxSettings();

            $payment = $this->createPayment($client, $taxSetting);

            $paymentUrl = $payment['init_point'];

            $subjetc = 'Safe Capital - Taxa do seguro';

            Mail::to($client->email)
                ->send(new PaymentMail(
                    $client->name,
                    $taxSetting->tax,
                    $paymentUrl,
                    $subjetc
                ));

            $usersToReceiveEmail = Helpers::getAdminAndManagerUsers();
            $subjetc = 'Novo cliente aceito';
            $message = "Cliente {$client->name} foi aceito pelo parceiro {$auth->name}.";

            foreach ($usersToReceiveEmail as $userToReceiveEmail) {
                Mail::to($userToReceiveEmail->email)
                    ->send(new DefaultMail(
                        $userToReceiveEmail->name,
                        $message,
                        $subjetc
                    ));
            }

            $client->status = ClientStatusEnum::WaitingPayment->value;
            $client->contract_number = Carbon::now()->format('YmdHis');
            $client->save();

            return ['status' => true, 'data' => $client];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function createPolicyDocument(Request $request)
    {
        try {
            $rules = [
                'client_id' => ['required', 'integer'],
                'attachments' => ['required', 'array'],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                throw new Exception($validator->errors()->first(), 400);
            }

            $client = Client::with('policys')->find($request->client_id);

            if (! isset($client)) {
                throw new Exception('Cliente não encontrado', 400);
            }

            if ($client->policys()->count()) {
                throw new Exception('Esse cliente já possui contrato anexado', 400);
            }

            $requestData = $request->all();

            foreach ($requestData['attachments'] as $attachment) {
                $file = $attachment['file'];
                $path = $file->store('policy-documents', 'public');
                $requestData['path'] = $path;
                $requestData['filename'] = $file->getClientOriginalName();

                $requestData['due_date'] = Carbon::now()->addYear();
                $requestData['contract_number'] = $client->contract_number ?? Carbon::now()->format('YmdHis');

                $policyDocument = PolicyDocument::create($requestData);
            }

            $client->status = ClientStatusEnum::WaitingAnalysis->value;
            $client->save();

            $auth = Auth::user();

            $usersToReceiveEmail = Helpers::getAdminAndManagerUsers();

            $message = "Contrato do cliente {$client->name} anexado pelo parceiro {$auth->name}.";
            $subjetc = 'Contrato anexado e aguardando validação';
            foreach ($usersToReceiveEmail as $userToReceiveEmail) {
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

    public function contractValidate($request, $client_id)
    {
        try {
            $rules = [
                'validation' => ['required', 'string', 'in:Accepted,Return,Refused'],
                'justification' => ['nullable', 'string', 'max:1000'],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            $clientToUpdate = Client::find($client_id);
            $user = $clientToUpdate->user;

            if (! isset($clientToUpdate)) {
                throw new Exception('Cliente não encontrado');
            }

            switch ($request->validation) {
                case UserValidationEnum::Accepted->value:
                    $clientToUpdate->status = ClientStatusEnum::Active->value;
                    $clientToUpdate->save();
                    Mail::to($user->email)
                        ->send(new AnalisyContractMail(
                            name: $user->name,
                            subject: 'Documentação aceita!',
                            textMessage: 'Sua documentação foi revisada e já foi aprovada!',
                            justification: $request->justification ?? ''
                        )
                        );
                    break;
                case UserValidationEnum::Return->value:
                    $clientToUpdate->status = ClientStatusEnum::WaitingContract->value;
                    $clientToUpdate->policys()->delete();
                    $clientToUpdate->save();
                    Mail::to($user->email)
                        ->send(new AnalisyContractMail(
                            name: $user->name,
                            subject: 'Documentação não aceita!',
                            textMessage: 'Sua documentação precisa de ajustes!',
                            justification: $request->justification ?? ''
                        ));
                    break;
                case UserValidationEnum::Refused->value:
                    $clientToUpdate->status = ClientStatusEnum::Inactive->value;
                    $clientToUpdate->save();
                    Mail::to($user->email)
                        ->send(new AnalisyContractMail(
                            name: $user->name,
                            subject: 'Documentação reprovada!',
                            textMessage: 'Sua documentação foi reprovada!',
                            justification: $request->justification ?? ''
                        ));
                    break;
                default:
                    throw new Exception('Validação inválida');
            }

            return ['status' => true, 'data' => $clientToUpdate];
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

    public function deleteAttachment($id)
    {
        try {
            $clientAttachment = ClientAttachment::find($id);

            if (! $clientAttachment) {
                throw new Exception('Cliente não encontrado');
            }

            $attachmentName = $clientAttachment->filename;
            $clientAttachment->delete();

            return ['status' => true, 'data' => $attachmentName];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    private function searchCustomerCreditHistory(Client $client, $hasCorresponding = false)
    {
        $cpf = $hasCorresponding ? $client->corresponding->cpf : $client->cpf;

        $response = $this->consultarCpf($cpf);
        if (! isset($response)) {
            return;
        }

        Log::info(json_encode($response));

        $spiderResult = $this->consultarProcessosPorCpf($cpf);

        $response['LawProcesses'] = $spiderResult['Result'][0]['Processes']['Lawsuits'] ?? [];

        ClientPh3Analisy::create([
            'client_id' => $client->id,
            'response' => json_encode($response),
        ]);

        return $response;
    }

    public function runSpiderForCpf(string $cpf, int $pollingSeconds = 2, int $maxAttempts = 7): array
    {
        Log::info('Iniciando processos');
        $startResponse = $this->startSpiderCpf($cpf);

        Log::info(json_encode($startResponse));
        $requestId = $startResponse['RequestId'] ?? null;

        if (! $requestId) {
            return ['Data' => []];
        }

        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep($pollingSeconds);

            $result = $this->getSpiderResult($requestId);

            if (! isset($result['Status'])) {
                return ['Data' => []];
            }

            if ($result['Status'] == 200 || $result['Status'] == 203) {
                return $result;
            }
        }

        return ['error' => 'Tempo limite excedido para resultado da consulta Spider'];
    }

    private function analizeClient($client, $response, $hascorresponding = false)
    {
        $settings = Helpers::getCreditSettings();

        $scores = collect($response[1]['dadosScoreRenda'] ?? []);
        $p5 = $scores->first(fn ($s) => strtolower($s['nome_score'] ?? '') === 'p5');
        $creditScore = (int) ($p5['score'] ?? ($scores->first()['score'] ?? 0));

        $lawSuits = collect($response['LawProcesses'] ?? []);

        $restricoes = collect($response[1]['restricoesDetalhe'] ?? []);
        $hasPendingIssues = $restricoes->isNotEmpty();
        $maxPendingValue = $hasPendingIssues ? (float) ($restricoes['valor'] ?? 0) : 0;

        $targetCpf = $hascorresponding ? $client->corresponding->cpf : $client->cpf;
        $judgedCategories = $this->extractProcessBroadSubjectsForCpf($lawSuits, $targetCpf);
        $hasJudgedLawProcesses = !empty($judgedCategories);

        $statusOrder = [
            ClientStatusEnum::Approved,
            ClientStatusEnum::Pending,
        ];

        foreach ($statusOrder as $status) {
            $matched = collect($settings)->first(function ($setting) use (
                $status, $creditScore, $hasPendingIssues, $maxPendingValue, $hasJudgedLawProcesses, $judgedCategories
            ) {
                $scoreOk = $creditScore >= $setting['start_score'] && $creditScore <= $setting['end_score'];
                $pendingOk = ! $hasPendingIssues || $setting['has_pending_issues'];
                $pendingValueOk = ! $hasPendingIssues || $setting['max_pending_value'] === null || $maxPendingValue <= $setting['max_pending_value'];

                $allowsLaw = (bool) $setting['has_law_processes'];
                $categoriesAllowed = collect($setting['process_categories'] ?? []);
                $categoriesOk = ! $hasJudgedLawProcesses || ($allowsLaw && collect($judgedCategories)->every(fn ($c) => $categoriesAllowed->contains($c)));

                return $setting['status'] === $status->value
                    && $scoreOk
                    && $categoriesOk
                    && $pendingOk
                    && $pendingValueOk;
            });

            if ($matched) {
                $client->status = $status;
                $client->save();
                return;
            }
        }

        if (! $hascorresponding) {
            $client->status = ClientStatusEnum::Disapproved;
            $client->save();
        }
    }

    private function extractProcessBroadSubjectsForCpf($lawSuits, string $cpf): array
    {
        return collect($lawSuits)
            ->filter(fn ($p) => $this->isClientJudgedInProcess($p, $cpf))
            ->pluck('InferredBroadCNJSubjectName')
            ->filter(fn ($v) => is_string($v) && $v !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function isClientJudgedInProcess(array $process, string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        $typesJudged = ['DEFENDANT', 'REU', 'RÉU'];
        return collect($process['Parties'] ?? [])
            ->contains(function ($party) use ($cpf, $typesJudged) {
                $doc = preg_replace('/\D/', '', (string) ($party['Doc'] ?? ''));
                $polarity = strtoupper((string) ($party['Polarity'] ?? ''));
                $type = strtoupper((string) ($party['Type'] ?? ''));
                return $doc === $cpf && ($polarity === 'PASSIVE' || in_array($type, $typesJudged, true));
            });
    }

    private function createPayment($client, $taxSetting)
    {

        $this->prepareMercadoPago(
            $client->email,
            $taxSetting->tax
        );

        $externalReference = (string) Str::uuid();
        $payment = $this->makePayment(externalReference: $externalReference);

        if (! isset($payment['init_point'])) {
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

    public function sendMessage(Request $request)
    {
        try {
            $subject = $request->subject;
            $message = $request->message;

            if (isset($request->client_id)) {
                $client = Client::find($request->client_id);
            } else {
                $client = Client::where('contract_number', $request->contract_number)->first();
            }

            if (! isset($client)) {
                throw new Exception('Cliente não encontrado', 400);
            }

            $user = $client->user;
            $textMessage = "<strong>Referente ao cliente</strong>: {$client->name}({$client->id})<br>";
            $textMessage .= nl2br($message);

            Mail::to($user->email)
                ->send(new DefaultMail(
                    name: $user->name,
                    message: $textMessage,
                    subjetc: $subject
                ));

            return ['status' => true, 'data' => null];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function makePolicy(Client $client)
    {
        $this->prepareDoc4Sign();

        $templatePath = storage_path('app/templates/policy_template.docx');
        $fileName = 'policy_'.Str::uuid().'.docx';
        $filledPath = storage_path('app/policies/'.$fileName);

        $template = new TemplateProcessor($templatePath);

        $template->setValue('contract_number', $client->contract_number);
        $template->setValue('date', now()->format('d/m/Y'));
        $template->setValue('policy_value', 'R$ '.number_format($client->policy_value, 2, ',', '.'));
        $template->setValue('month_value', 'R$ '.number_format(($client->policy_value / 12), 2, ',', '.'));

        $template->setValue('address', $client->street);
        $template->setValue('number', $client->number);
        $template->setValue('complement', $client->complement ?? '');
        $template->setValue('cep', $client->cep);
        $template->setValue('property_type', $client->property_type->label());
        $template->setValue('neighborhood', $client->neighborhood);
        $template->setValue('city', $client->city);
        $template->setValue('state', $client->state);

        $template->setValue('name', $client->name.' '.$client->surname);
        $template->setValue('cpf', $client->cpf);
        $template->setValue('email', $client->email);
        $template->setValue('birthday', Carbon::parse($client->birthday)->format('d/m/Y'));
        $template->setValue('phone', $client->phone);

        $template->setValue('corresponding_name', $client->corresponding?->name ?? '');
        $template->setValue('corresponding_cpf', $client->corresponding->cpf ?? '');
        $template->setValue('corresponding_email', $client->corresponding?->email ?? '');
        $template->setValue('corresponding_birthday', $client->corresponding?->birthday?->format('d/m/Y') ?? '');
        $template->setValue('corresponding_phone', $client->corresponding?->phone ?? '');

        $template->saveAs($filledPath);

        $this->prepareDoc4Sign();

        $upload = $this->uploadDocument($filledPath, $fileName);
        $documentUuid = $upload['uuid'];

        $client->doc4sign_document_uuid = $documentUuid;
        $client->save();

        $this->createSigner($documentUuid, [
            [
                'email' => $client->email,
                'act' => '1',
                'foreign' => '0',
                'certificadoicpbr' => '0',
                'assinatura_presencial' => '0',
                'docauthandselfie' => '1',
                'embed_methodauth' => 'email',
                'upload_allow' => '0',
                'certificadoicpbr_cpf' => $client->cpf,
            ],
        ]);

        $this->sendToSign($documentUuid);

        $this->registerWebhook($documentUuid, route('webhook.d4sign'));
    }

    private function extractAnalisyFields(array $response): array
    {
        $scores = collect($response[1]['dadosScoreRenda'] ?? []);
        $p5 = $scores->first(fn ($s) => strtolower($s['nome_score'] ?? '') === 'p5');
        $score = (int) ($p5['score'] ?? ($scores->first()['score'] ?? 0));

        $hasProcesses = isset($response['LawProcesses']) && count($response['LawProcesses']) > 0;

        $restricoes = collect($response[1]['restricoesDetalhe'] ?? []);
        $hasPendings = $restricoes->isNotEmpty();

        return [$score, $hasPendings, $hasProcesses];
    }

}

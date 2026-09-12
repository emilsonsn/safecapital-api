<?php

namespace App\Http\Controllers;

use App\Enums\UserRoleEnum;
use App\Models\Client;
use App\Models\ClientInstallment;
use App\Services\Client\ClientInstallmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientInstallmentController extends Controller
{
    private $installmentService;

    public function __construct(ClientInstallmentService $installmentService)
    {
        $this->installmentService = $installmentService;
    }

    public function listByClient($clientId)
    {
        $client = Client::find($clientId);

        if (! $client || ! $this->canAccessClient($client)) {
            return $this->response([
                'status' => false,
                'error' => 'Cliente não encontrado',
                'statusCode' => 404,
            ]);
        }

        $result = $this->installmentService->listByClient($clientId);

        if ($result['status']) {
            $result['message'] = 'Parcelas listadas com sucesso';
        }

        return $this->response($result);
    }

    public function uploadProof(Request $request, $id)
    {
        $installment = ClientInstallment::with('client')->find($id);

        if (! $installment || ! $this->canAccessClient($installment->client)) {
            return $this->response([
                'status' => false,
                'error' => 'Parcela não encontrada',
                'statusCode' => 404,
            ]);
        }

        $result = $this->installmentService->uploadPaymentProof($request, $installment);

        if ($result['status']) {
            $result['message'] = 'Comprovante enviado com sucesso';
        }

        return $this->response($result);
    }

    public function markAsPaid($id)
    {
        $installment = ClientInstallment::with('client')->find($id);

        if (! $installment || ! $this->canAccessClient($installment->client)) {
            return $this->response([
                'status' => false,
                'error' => 'Parcela não encontrada',
                'statusCode' => 404,
            ]);
        }

        $result = $this->installmentService->markAsPaid($installment);

        if ($result['status']) {
            $result['message'] = 'Parcela marcada como paga com sucesso';
        }

        return $this->response($result);
    }

    private function canAccessClient(?Client $client): bool
    {
        if (! $client) {
            return false;
        }

        $user = Auth::user();

        return in_array($user->role, [UserRoleEnum::Admin->value, UserRoleEnum::Manager->value], true)
            || $client->user_id === $user->id;
    }

    private function response($result)
    {
        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'] ?? null,
            'data' => $result['data'] ?? null,
            'error' => $result['error'] ?? null,
        ], $result['statusCode'] ?? 200);
    }
}

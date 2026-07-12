<?php

use App\Http\Controllers\ClientInstallmentController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\ClienteValidationMiddleware;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CreditConfigurationController;
use App\Http\Controllers\SolicitationController;
use App\Http\Controllers\TaxSettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InvoiceController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('login', [AuthController::class, 'login']);

Route::get('validateToken', [AuthController::class, 'validateToken']);
Route::post('recoverPassword', [UserController::class, 'passwordRecovery']);
Route::post('updatePassword', [UserController::class, 'updatePassword']);

Route::get('validateToken', [AuthController::class, 'validateToken']);

Route::prefix('user')->group(function(){
    Route::post('create', [UserController::class, 'create']);
    Route::get('email', [UserController::class, 'getByEmail']);
});

Route::middleware('jwt')->prefix('user')->group(function(){
    Route::patch('{id}', [UserController::class, 'update']);
});

Route::middleware(['jwt'])->group(function(){

    Route::prefix('user')->group(function(){        
        Route::get('me', [UserController::class, 'getUser']);
        Route::post('accept-term', [UserController::class, 'AcceptTerm']);
    });

    Route::post('logout', [AuthController::class, 'logout']);

    Route::middleware(['clienteAcceptTerms', 'clientValidation'])->group(function() {
    
        Route::prefix('user')->group(function(){
            Route::get('all', [UserController::class, 'all']);
            Route::get('search', [UserController::class, 'search']);
            Route::get('cards', [UserController::class, 'cards']);
            Route::delete('{id}', [UserController::class, 'delete']);
            Route::delete('attachment/{id}', [UserController::class, 'deleteAttachment']);        
            Route::patch('validation/{id}', [UserController::class, 'validation']);
            Route::post('block/{id}', [UserController::class, 'userBlock']);
        });
    
        Route::prefix('solicitation')->group(function(){
            Route::get('search', [SolicitationController::class, 'search']);
            Route::get('{id}', [SolicitationController::class, 'getById']);
            Route::post('create', [SolicitationController::class, 'create']);
            Route::patch('{id}', [SolicitationController::class, 'update']);
            Route::patch('/close/{id}', [SolicitationController::class, 'close']);
            Route::post('create-message', [SolicitationController::class, 'createMessage']);
            Route::delete('{id}', [SolicitationController::class, 'delete']);   
            Route::delete('item/{id}', [SolicitationController::class, 'deleteItem']);                    
        });
    
        Route::prefix('credit-configuration')->group(function(){
            Route::get('search', [CreditConfigurationController::class, 'search']);
            Route::patch('create', [CreditConfigurationController::class, 'create']);
            Route::patch('{id}', [CreditConfigurationController::class, 'update']);    
            Route::delete('{id}', [CreditConfigurationController::class, 'delete']);
        });

        Route::prefix('tax-setting')->group(function(){
            Route::get('search', [TaxSettingController::class, 'search']);
            Route::patch('{id}', [TaxSettingController::class, 'update']);    
        });
    
        Route::prefix('client')->group(function(){
            Route::get('search', [ClientController::class, 'search']);
            Route::post('create', [ClientController::class, 'create']);
            Route::post('policy-document', [ClientController::class, 'createPolicyDocument']);
            Route::post('send-message', [ClientController::class, 'sendMessage']);
            Route::patch('policy/{id}', [ClientController::class, 'updatePolicyDocument']);
            Route::patch('accept/{id}', [ClientController::class, 'accept']);
            Route::post('{id}/contract/analisys', [ClientController::class, 'contractValidate']);
            Route::patch('{id}', [ClientController::class, 'update']);
            Route::delete('policy/{id}', [ClientController::class, 'deletePolicyDocument']);
            Route::delete('attachment/{id}', [ClientController::class, 'deleteAttachment']);
            Route::delete('{id}', [ClientController::class, 'delete']);        
        });

        Route::prefix('client-installment')->group(function(){
            Route::get('client/{clientId}', [ClientInstallmentController::class, 'listByClient']);
            Route::post('{id}/upload-proof', [ClientInstallmentController::class, 'uploadProof']);            
            Route::patch('{id}/mark-as-paid', [ClientInstallmentController::class, 'markAsPaid']);
        });        

        Route::get('finance/invoices', [InvoiceController::class, 'index']);
    });
});

Route::get('/btg/callback', function (Request $request) {
    if ($request->filled('error')) {
        Log::error('Erro no callback OAuth BTG', [
            'error' => $request->query('error'),
            'error_description' => $request->query('error_description'),
        ]);

        return response()->json([
            'message' => 'Erro retornado pelo BTG.',
        ], 400);
    }

    $code = $request->query('code');

    if (!$code) {
        return response()->json([
            'message' => 'Código de autorização não recebido.',
        ], 400);
    }

    $response = Http::asForm()
        ->withBasicAuth(config('services.btg.client_id'), config('services.btg.client_secret'))
        ->post(config('services.btg.token_url'), [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => config('services.btg.redirect_uri'),
        ]);

    if ($response->failed()) {
        Log::error('Erro ao autenticar no BTG', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        return response()->json([
            'message' => 'Erro ao autenticar no BTG.',
        ], 400);
    }

    $data = $response->json();

    Log::info('BTG OAuth autenticado com sucesso.');

    return response()->json([
        'message' => 'Autenticação BTG realizada com sucesso.',
        'access_token_received' => isset($data['access_token']),
        'refresh_token_received' => isset($data['refresh_token']),
    ]);
});

Route::prefix('webhook')->group(function () {
    Route::post('payment', [WebhookController::class, 'mercadopago']);
    Route::post('d4sign', [WebhookController::class, 'd4sign'])
        ->name('webhook.d4sign');
});

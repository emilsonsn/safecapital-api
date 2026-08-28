<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CreditConfigurationController;
use App\Http\Controllers\SolicitationController;
use App\Http\Controllers\TaxSettingController;
use App\Http\Controllers\TermDocumentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

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

Route::prefix('user')->group(function () {
    Route::post('create', [UserController::class, 'create']);
    Route::get('email', [UserController::class, 'getByEmail']);
});

Route::middleware('jwt')->prefix('user')->group(function () {
    Route::patch('{id}', [UserController::class, 'update']);
});

Route::middleware(['jwt'])->group(function () {

    Route::get('term/current', [TermDocumentController::class, 'current']);
    Route::post('term', [TermDocumentController::class, 'store'])
        ->middleware('adminOrManager');

    Route::prefix('user')->group(function () {
        Route::get('me', [UserController::class, 'getUser']);
        Route::post('accept-term', [UserController::class, 'AcceptTerm']);
    });

    Route::post('logout', [AuthController::class, 'logout']);

    Route::middleware(['clienteAcceptTerms', 'clientValidation'])->group(function () {

        Route::prefix('user')->group(function () {
            Route::get('all', [UserController::class, 'all']);
            Route::get('search', [UserController::class, 'search']);
            Route::get('cards', [UserController::class, 'cards']);
            Route::delete('{id}', [UserController::class, 'delete']);
            Route::delete('attachment/{id}', [UserController::class, 'deleteAttachment']);
            Route::patch('validation/{id}', [UserController::class, 'validation']);
            Route::post('block/{id}', [UserController::class, 'userBlock']);
        });

        Route::prefix('solicitation')->group(function () {
            Route::get('search', [SolicitationController::class, 'search']);
            Route::get('{id}', [SolicitationController::class, 'getById']);
            Route::post('create', [SolicitationController::class, 'create']);
            Route::patch('{id}', [SolicitationController::class, 'update']);
            Route::patch('/close/{id}', [SolicitationController::class, 'close']);
            Route::post('create-message', [SolicitationController::class, 'createMessage']);
            Route::delete('{id}', [SolicitationController::class, 'delete']);
            Route::delete('item/{id}', [SolicitationController::class, 'deleteItem']);
        });

        Route::prefix('credit-configuration')->group(function () {
            Route::get('search', [CreditConfigurationController::class, 'search']);
            Route::patch('create', [CreditConfigurationController::class, 'create']);
            Route::patch('{id}', [CreditConfigurationController::class, 'update']);
            Route::delete('{id}', [CreditConfigurationController::class, 'delete']);
        });

        Route::prefix('tax-setting')->group(function () {
            Route::get('search', [TaxSettingController::class, 'search']);
            Route::patch('{id}', [TaxSettingController::class, 'update']);
        });

        Route::prefix('client')->group(function () {
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
    });
});

Route::get('/btg/callback', function (Request $request) {
    Log::info('BTG OAuth Callback recebido', [
        'query' => $request->query(),
        'code' => $request->query('code'),
        'state' => $request->query('state'),
        'error' => $request->query('error'),
        'error_description' => $request->query('error_description'),
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
    ]);

    return response()->json([
        'message' => 'Callback BTG recebido com sucesso. Verifique o log da aplicação.',
        'code_received' => $request->has('code'),
    ]);
});

Route::prefix('webhook')->group(function () {
    Route::post('payment', [WebhookController::class, 'mercadopago']);
    Route::post('d4sign', [WebhookController::class, 'd4sign'])
        ->name('webhook.d4sign');
});

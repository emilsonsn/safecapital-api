<?php

use App\Http\Controllers\AdminCashflowController;
use App\Http\Controllers\AdminInvoiceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BtgIntegrationController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientInstallmentController;
use App\Http\Controllers\CreditConfigurationController;
use App\Http\Controllers\FinancialDashboardController;
use App\Http\Controllers\FinancialReportExportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SolicitationController;
use App\Http\Controllers\TaxSettingController;
use App\Http\Controllers\TermDocumentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\FinancialMiddleware;
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

    Route::middleware(AdminMiddleware::class)->prefix('admin/integrations/btg')->group(function () {
        Route::get('/', [BtgIntegrationController::class, 'show']);
        Route::post('connect', [BtgIntegrationController::class, 'connect']);
        Route::post('refresh', [BtgIntegrationController::class, 'refresh']);
        Route::delete('/', [BtgIntegrationController::class, 'disconnect']);
    });

    Route::middleware(FinancialMiddleware::class)->prefix('admin/finance')->group(function () {
        Route::get('clients', [AdminInvoiceController::class, 'clients']);
        Route::get('clients/{user}/invoices', [AdminInvoiceController::class, 'invoices']);
        Route::patch('clients/{user}/invoices/{invoice}/mark-as-paid', [AdminInvoiceController::class, 'markAsPaid']);

        Route::get('suppliers', [AdminCashflowController::class, 'suppliers']);
        Route::post('suppliers', [AdminCashflowController::class, 'storeSupplier']);
        Route::patch('suppliers/{supplier}', [AdminCashflowController::class, 'updateSupplier']);

        Route::get('expenses', [AdminCashflowController::class, 'expenses']);
        Route::post('expenses', [AdminCashflowController::class, 'storeExpense']);
        Route::patch('expenses/{expense}', [AdminCashflowController::class, 'updateExpense']);
        Route::patch('expenses/{expense}/mark-as-paid', [AdminCashflowController::class, 'markExpenseAsPaid']);

        Route::get('recoverables', [AdminCashflowController::class, 'recoverables']);
        Route::post('recoverables', [AdminCashflowController::class, 'storeRecoverable']);
        Route::patch('recoverables/{recoverable}', [AdminCashflowController::class, 'updateRecoverable']);
        Route::patch('recoverables/{recoverable}/mark-as-received', [AdminCashflowController::class, 'markRecoverableAsReceived']);
        Route::patch('recoverables/{recoverable}/mark-as-lost', [AdminCashflowController::class, 'markRecoverableAsLost']);

        Route::get('reports/monthly', [AdminCashflowController::class, 'monthlyReport']);
        Route::get('reports/monthly/history', [AdminCashflowController::class, 'monthlyReports']);
        Route::get('reports/monthly/export', [FinancialReportExportController::class, 'monthly']);
        Route::get('dashboard', [FinancialDashboardController::class, 'index']);
    });

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

        Route::prefix('client-installment')->group(function () {
            Route::get('client/{clientId}', [ClientInstallmentController::class, 'listByClient']);
            Route::post('{id}/upload-proof', [ClientInstallmentController::class, 'uploadProof']);
            Route::patch('{id}/mark-as-paid', [ClientInstallmentController::class, 'markAsPaid']);
        });

        Route::get('finance/invoices', [InvoiceController::class, 'index']);
    });
});

Route::get('/btg/callback', [BtgIntegrationController::class, 'callback'])
    ->middleware('throttle:20,1');

Route::prefix('webhook')->group(function () {
    Route::post('payment', [WebhookController::class, 'mercadopago']);
    Route::post('d4sign', [WebhookController::class, 'd4sign'])
        ->name('webhook.d4sign');
});

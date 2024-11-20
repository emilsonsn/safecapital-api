<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AdminMiddleware;

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
});

Route::middleware('jwt')->group(function(){

    Route::middleware(AdminMiddleware::class)->group(function() {
        // Middleware do admin
    });

    Route::post('logout', [AuthController::class, 'logout']);

    Route::prefix('user')->group(function(){
        Route::get('all', [UserController::class, 'all']);
        Route::get('search', [UserController::class, 'search']);
        Route::get('cards', [UserController::class, 'cards']);
        Route::get('me', [UserController::class, 'getUser']);
        Route::delete('attachment/{id}', [UserController::class, 'deleteAttachment']);        
        Route::patch('validation/{id}', [UserController::class, 'validation']);
        Route::patch('{id}', [UserController::class, 'update']);
        Route::post('block/{id}', [UserController::class, 'userBlock']);
    });

    Route::prefix('client')->group(function(){
        Route::get('search', [ClientController::class, 'search']);
        Route::post('create', [ClientController::class, 'create']);
        Route::patch('{id}', [ClientController::class, 'update']);
        Route::delete('attachment/{id}', [ClientController::class, 'deleteAttachment']);
        Route::delete('{id}', [ClientController::class, 'delete']);        
    });
});
<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CanteenController;
use App\Http\Controllers\Api\V1\FundsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => [
        'ok' => true,
        'name' => config('app.name'),
        'version' => '1',
    ]);

    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::get('/canteen/catalog', [CanteenController::class, 'catalog']);
        Route::get('/canteen/today', [CanteenController::class, 'today']);
        Route::post('/canteen/today', [CanteenController::class, 'store']);
        Route::get('/canteen/meals', [CanteenController::class, 'meals']);
        Route::get('/canteen/meals/{meal}', [CanteenController::class, 'show']);
        Route::post('/canteen/meals/{meal}/request-edit', [CanteenController::class, 'requestEdit']);
        Route::get('/canteen/review', [CanteenController::class, 'review']);
        Route::post('/canteen/meals/{meal}/approve', [CanteenController::class, 'approve']);
        Route::post('/canteen/meals/{meal}/refuse', [CanteenController::class, 'refuse']);

        Route::get('/departments', [FundsController::class, 'departments']);
        Route::get('/budgets', [FundsController::class, 'budgets']);
        Route::get('/requisitions', [FundsController::class, 'requisitions']);
        Route::post('/requisitions', [FundsController::class, 'storeRequisition']);
        Route::get('/requisitions/{requisition}', [FundsController::class, 'showRequisition']);
        Route::post('/requisitions/{requisition}/initiate', [FundsController::class, 'initiate']);
        Route::post('/requisitions/{requisition}/approve', [FundsController::class, 'approve']);
        Route::post('/requisitions/{requisition}/reject', [FundsController::class, 'reject']);
        Route::post('/requisitions/{requisition}/disburse', [FundsController::class, 'disburse']);
        Route::post('/requisitions/{requisition}/account', [FundsController::class, 'account']);
        Route::post('/requisitions/{requisition}/close', [FundsController::class, 'close']);

        Route::get('/petty-cash', [FundsController::class, 'pettyCash']);
        Route::get('/petty-cash/{petty_cash_fund}', [FundsController::class, 'pettyCashShow']);
        Route::post('/petty-cash/{petty_cash_fund}/topup', [FundsController::class, 'pettyCashTopup']);
    });
});

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SMSController;
use App\Http\Controllers\DebtorController;

Route::prefix('debtors')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [DebtorController::class, 'store']);
    Route::get('/', [DebtorController::class, 'index']);
    Route::get('/receivables', [DebtorController::class, 'receivables']);
    Route::get('/payables', [DebtorController::class, 'payables']);
    Route::get('/{id}', [DebtorController::class, 'show']);
    Route::put('/{id}', [DebtorController::class, 'update']);
    Route::patch('/{id}/archive', [DebtorController::class, 'archive']);
    Route::post('/{id}/make-payment', [DebtorController::class, 'makePayment']);
    Route::get('/contacts', [DebtorController::class, 'showContacts']);
});

Route::post('/send-sms-reminder/{debtorId}', [SMSController::class, 'sendReminder']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/verify-login-otp', [AuthController::class, 'verifyLoginOtp']);


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

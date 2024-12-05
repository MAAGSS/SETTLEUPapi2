<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SMSController;
use App\Http\Controllers\DebtorController;

Route::middleware('auth:sanctum')->group(function () {
Route::get('/debtors/receivables', [DebtorController::class, 'receivables']);
Route::get('/debtors/payables', [DebtorController::class, 'payables']);

Route::get('/debtors/{id}', [DebtorController::class, 'show']);

Route::patch('/debtors/{id}/archive', [DebtorController::class, 'archive']);


Route::put('/debtors/{id}', [DebtorController::class, 'update']);


Route::get('/debtors', [DebtorController::class, 'index']);


Route::post('/debtors', [DebtorController::class, 'store']);

Route::post('/send-sms', [SMSController::class, 'sendSMS']);
});
Route::post('/send-sms-reminder/{debtorId}', [SMSController::class, 'sendReminder']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/verify-login-otp', [AuthController::class, 'verifyLoginOtp']);


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

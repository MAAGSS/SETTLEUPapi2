<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SMSController;
use App\Http\Controllers\DebtorController;


Route::get('/debtors/receivables', [DebtorController::class, 'receivables']);
Route::get('/debtors/payables', [DebtorController::class, 'payables']);
// Route to show a specific debtor's details
Route::get('/debtors/{id}', [DebtorController::class, 'show']);
// Archive or unarchive a debtor
Route::patch('/debtors/{id}/archive', [DebtorController::class, 'archive']);

// Update a debtor's information
Route::put('/debtors/{id}', [DebtorController::class, 'update']);

// Get all debtors
Route::get('/debtors', [DebtorController::class, 'index']);

// Add a new debtor
Route::post('/debtors', [DebtorController::class, 'store']);

Route::post('/send-sms', [SMSController::class, 'sendSMS']);
// Register and Login routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/verify-login-otp', [AuthController::class, 'verifyLoginOtp']);

// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

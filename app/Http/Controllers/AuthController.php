<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 400);
    }

    // Generate OTP
    $otp = rand(100000, 999999);
    
    // Cache the OTP and user details for 5 minutes
    cache()->put('otp_'.$request->email, [
        'otp' => $otp,
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password)
    ], 300); // Data expires in 5 minutes

    // Send OTP to user's email
    Mail::to($request->email)->send(new OtpMail($otp));

    return response()->json(['message' => 'OTP sent to your email']);
}



public function verifyOtp(Request $request)
{
    $validator = Validator::make($request->all(), [
        'otp' => 'required|digits:6',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 400);
    }

    // Retrieve the email from the header
    $email = $request->header('Email'); // or use another method to send the email

    // Retrieve cached data (OTP and user details)
    $cachedData = cache()->get('otp_'.$email);

    // Check if the cached data exists and the OTP matches
    if (!$cachedData || $cachedData['otp'] != $request->otp) {
        return response()->json(['message' => 'Invalid OTP or OTP expired'], 400);
    }

    // OTP is correct, create the user
    $user = User::create([
        'name' => $cachedData['name'],
        'email' => $cachedData['email'],
        'password' => $cachedData['password'],
    ]);

    // Clear the cached data after successful verification
    cache()->forget('otp_'.$email);

    // Generate token for the user
    $token = $user->createToken('Personal Access Token')->plainTextToken;

    return response()->json(['user' => $user, 'token' => $token], 200);
}
public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|string|email',
        'password' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 400);
    }

    // Check email and password
    if (!Auth::attempt($request->only('email', 'password'))) {
        return response()->json(['message' => 'Invalid login credentials'], 401);
    }

   

    
    $user = Auth::user();
    

    return response()->json(['message' => 'Successfully logged in']);
}

// OTP Verification for Login (Step 2)
public function verifyLoginOtp(Request $request)
{
    $validator = Validator::make($request->all(), [
        'otp' => 'required|digits:6',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 400);
    }

    // Retrieve email from the header (or use session)
    $email = $request->header('Email');

    // Retrieve cached OTP for the login process
    $cachedOtp = cache()->get('otp_login_'.$email);

    if (!$cachedOtp || $cachedOtp != $request->otp) {
        return response()->json(['message' => 'Invalid OTP or OTP expired'], 400);
    }

    // OTP is correct, clear cached OTP
    cache()->forget('otp_login_'.$email);

    // Generate token for the user
    $user = User::where('email', $email)->first();
    $token = $user->createToken('Personal Access Token')->plainTextToken;

    return response()->json(['message' => 'Login successful', 'user' => $user, 'token' => $token], 200);
}

}

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

        $otp = rand(100000, 999999);

        cache()->put('otp_' . $request->email, [
            'otp' => $otp,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ], 300); // Data expires in 5 minutes

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

        $email = $request->header('Email');

        $cachedData = cache()->get('otp_' . $email);

        if (!$cachedData || $cachedData['otp'] != $request->otp) {
            return response()->json(['message' => 'Invalid OTP or OTP expired'], 400);
        }

        $user = User::create([
            'name' => $cachedData['name'],
            'email' => $cachedData['email'],
            'password' => $cachedData['password'],
        ]);

        cache()->forget('otp_' . $email);

        $token = $user->createToken('Personal Access Token')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token], 200);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }

        $user = $request->user();
        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function verifyLoginOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $email = $request->header('Email');

        $cachedOtp = cache()->get('otp_login_' . $email);

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            return response()->json(['message' => 'Invalid OTP or OTP expired'], 400);
        }

        cache()->forget('otp_login_' . $email);

        $user = User::where('email', $email)->first();
        $token = $user->createToken('Personal Access Token')->plainTextToken;

        return response()->json(['message' => 'Login successful', 'user' => $user, 'token' => $token], 200);
    }

    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}

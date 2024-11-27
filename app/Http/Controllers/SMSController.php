<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SMSController extends Controller
{
    public function sendSMS(Request $request)
    {
        try {
            $validated = $request->validate([
                'to' => 'required|string',
                'message' => 'required|string',
            ]);

            $baseUrl = 'http://192.168.1.6:8082';
            $token = 'd1f02324-c1fa-4490-9059-c2860ac5df6d';

            $testResponse = Http::get($baseUrl);
            Log::info('Testing Gateway Connection', [
                'status' => $testResponse->status(),
                'body' => $testResponse->body()
            ]);

            $response = Http::withHeaders([
                'X-API-KEY' => $token,  
                'Content-Type' => 'application/json'
            ])->post($baseUrl . '/send', [
                'to' => $validated['to'],
                'message' => $validated['message']
            ]);

            Log::info('SMS Gateway Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'response' => $response->json()
                ], 200);
            }

            $alternativeResponse = Http::withHeaders([
                'Authorization' => $token,
                'Content-Type' => 'application/json'
            ])->post($baseUrl . '/send', [
                'to' => $validated['to'],
                'message' => $validated['message']
            ]);

            if ($alternativeResponse->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'SMS sent successfully (alternative auth)',
                    'response' => $alternativeResponse->json()
                ], 200);
            }

            return response()->json([
                'error' => 'Failed to send SMS.',
                'status' => $response->status(),
                'details' => $response->body() ?: 'No response from gateway',
                'alt_status' => $alternativeResponse->status(),
                'alt_details' => $alternativeResponse->body()
            ], 500);

        } catch (\Exception $e) {
            Log::error('SMS Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'SMS Exception',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
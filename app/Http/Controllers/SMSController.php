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

            // Base configuration
            $baseUrl = 'http://192.168.1.6:8082';
            $token = 'f81a4663-0d18-4667-9327-9fa284a71132';

            // First, try to authenticate/verify connection
            $testResponse = Http::get($baseUrl);
            Log::info('Testing Gateway Connection', [
                'status' => $testResponse->status(),
                'body' => $testResponse->body()
            ]);

            // Send SMS with modified headers
            $response = Http::withHeaders([
                'X-API-KEY' => $token,  // Try alternative header
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

            // If first attempt fails, try alternative authentication
            $alternativeResponse = Http::withHeaders([
                'Authorization' => $token,  // Try without Bearer
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
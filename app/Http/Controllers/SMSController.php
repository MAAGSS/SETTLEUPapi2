<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Debtor; // Import the Debtor model
use Illuminate\Support\Facades\Mail;
use App\Mail\DueDateReminder;

class SMSController extends Controller
{
    public function sendSMS(Request $request)
    {
        try {
            // Validate the incoming request to ensure debtor_id is provided
            $validated = $request->validate([
                'debtor_id' => 'required|integer|exists:debtors,id', // Make sure debtor_id exists in the database
                'message' => 'required|string',
            ]);

            // Retrieve debtor information using debtor_id
            $debtor = Debtor::find($validated['debtor_id']);

            if (!$debtor) {
                return response()->json([
                    'error' => 'Debtor not found',
                    'status' => 404
                ], 404);
            }

            // Ensure we have a valid contact number from the debtor
            $contactNumber = $debtor->contact_number;

            if (empty($contactNumber)) {
                return response()->json([
                    'error' => 'Debtor does not have a contact number',
                    'status' => 400
                ], 400);
            }

            $baseUrl = 'http://192.168.1.6:8082';
            $token = 'd1f02324-c1fa-4490-9059-c2860ac5df6d';

            // Test the gateway connection (optional)
            $testResponse = Http::get($baseUrl);
            Log::info('Testing Gateway Connection', [
                'status' => $testResponse->status(),
                'body' => $testResponse->body()
            ]);

            // Send SMS using the contact number retrieved from debtor record
            $response = Http::withHeaders([
                'X-API-KEY' => $token,
                'Content-Type' => 'application/json'
            ])->post($baseUrl . '/send', [
                'to' => $contactNumber,
                'message' => $validated['message']
            ]);

            Log::info('SMS Gateway Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);
            Mail::to($debtor->email)->send(new DueDateReminder($debtor));
            // Check if the request was successful
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'response' => $response->json()
                ], 200);
            }

            // Alternative request with different authentication method
            $alternativeResponse = Http::withHeaders([
                'Authorization' => $token,
                'Content-Type' => 'application/json'
            ])->post($baseUrl . '/send', [
                'to' => $contactNumber,
                'message' => $validated['message']
            ]);

            if ($alternativeResponse->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'SMS sent successfully (alternative auth)',
                    'response' => $alternativeResponse->json()
                ], 200);
            }

            // Return failure details if both requests fail
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

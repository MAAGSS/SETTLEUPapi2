<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Debtor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\DebtorContact;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentConfirmationMail;

class DebtorController extends Controller
{

    public function store(Request $request)
{
    if (!auth()->check()) {
        return response()->json([
            'success' => false,
            'message' => 'User must be authenticated'
        ], 401);
    }

    $request->validate([
        'name' => 'required|string|max:255',
        'contact_number' => 'required|string|max:15',
        'email' => 'required|email',
        'amount_to_borrow' => 'required|numeric|min:0',
        'start_date' => 'required|date',
        'due_date' => 'required|date',
        'interest_rate' => 'required|numeric|min:0|max:100',
        'debt_type' => 'required|in:receivable,payable'
    ]);

    // Ensure contact is unique for this user
    $debtorContact = DebtorContact::firstOrCreate(
        [
            'email' => $request->email,
            'usr_id' => auth()->id()
        ],
        [
            'name' => $request->name,
            'contact_number' => $request->contact_number,
            'credit_score' => 80 // Default credit score
        ]
    );

    // Calculate total amount
    $totalAmount = $request->amount_to_borrow + 
                   ($request->amount_to_borrow * $request->interest_rate / 100);

    // Create debtor record
    $debtor = Debtor::create(array_merge(
        $request->all(),
        [
            'usr_id' => auth()->id(),
            'total_amount' => $totalAmount
        ]
    ));

    return response()->json([
        'success' => true,
        'message' => 'Debtor and contact information stored successfully.',
        'data' => [
            'debtor' => $debtor,
            'debtor_contact' => $debtorContact,
        ],
    ], 201);
}

    public function index(Request $request)
{
    $isArchived = $request->query('is_archived', false); // Default: show active

    $debtors = Debtor::where('usr_id', Auth::id()) // Filter by user ID
                     ->where('is_archived', $isArchived)
                     ->get();

    $result = $debtors->map(function ($debtor) {
        return [
            'name' => $debtor->name,
            'total_amount' => $debtor->total_amount, // Calculated dynamically
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $result,
    ]);
}


public function update(Request $request, $id)
{

    $request->validate([
        'name' => 'nullable|string|max:255',
        'contact_number' => 'nullable|string|max:15',
        'email' => 'nullable|email|unique:debtors,email,' . $id, 
        'amount_to_borrow' => 'nullable|numeric|min:0',
        'start_date' => 'nullable|required|date',
            'due_date' => 'nullable|required|date',
        'interest_rate' => 'nullable|numeric|min:0|max:100',
    ]);


    $debtor = Debtor::where('id', $id)->where('usr_id', Auth::id())->first();


    if (!$debtor) {
        return response()->json([
            'success' => false,
            'message' => 'Debtor not found.',
        ], 404);
    }


    $debtor->update($request->all());


    return response()->json([
        'success' => true,
        'message' => 'Debtor information updated successfully.',
        'data' => $debtor,
    ]);
}

public function archive($id)
{

    $debtor = Debtor::where('id', $id)->where('usr_id', Auth::id())->first();


    if (!$debtor) {
        return response()->json([
            'success' => false,
            'message' => 'Debtor not found.',
        ], 404);
    }


    $debtor->is_archived = !$debtor->is_archived;
    $debtor->save();


    return response()->json([
        'success' => true,
        'message' => $debtor->is_archived ? 'Debtor archived successfully.' : 'Debtor unarchived successfully.',
        'data' => $debtor,
    ]);
}

public function show($id)
{
    $debtor = Debtor::where('id', $id)->where('usr_id', Auth::id())->first();

    if (!$debtor) {
        return response()->json([
            'success' => false,
            'message' => 'Debtor not found.',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => [
            'id' => $debtor->id,
            'debt_id' => $debtor->debt_id,
            'name' => $debtor->name,
            'contact_number' => $debtor->contact_number,
            'email' => $debtor->email,
            'amount_to_borrow' => $debtor->amount_to_borrow,
            'interest_rate' => $debtor->interest_rate,
            'start_date' => $debtor->start_date,
            'due_date' => $debtor->due_date,
            'total_amount' => $debtor->total_amount,
        ],
    ]);
}


public function receivables()
{
    $debtors = Debtor::where('usr_id', Auth::id())
                 ->where('debt_type', 'receivable') // Or 'payable'
                 ->where('is_archived', false)
                 ->get();


    return response()->json([
        'success' => true,
        'data' => $debtors->map(function ($debtor) {
            return [
                'id' => $debtor->id,
                'name' => $debtor->name,
                'total_amount' => $debtor->total_amount,
                'start_date' => $debtor->start_date,
                'due_date' => $debtor->due_date,
            ];
        }),
    ]);
}



public function payables()
{
    $debtors = Debtor::where('usr_id', Auth::id())
                 ->where('debt_type', 'payable') // Or 'payable'
                 ->where('is_archived', false)
                 ->get();


    return response()->json([
        'success' => true,
        'data' => $debtors->map(function ($debtor) {
            return [
                'id' => $debtor->id,
                'name' => $debtor->name,
                'total_amount' => $debtor->total_amount,
            ];
        }),
    ]);
}

public function showContacts(Request $request)
    {
        // Ensure user is authenticated
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'User must be authenticated',
            ], 401);
        }

        // Fetch debtor contacts for the authenticated user
        $debtorContacts = DebtorContact::where('usr_id', auth()->id())->get();

        return response()->json([
            'success' => true,
            'data' => $debtorContacts,
        ]);
    }
    public function makePayment(Request $request, $id)
{
    // Validate the payment input
    $request->validate([
        'payment_amount' => 'required|numeric|min:0.01'
    ]);

    // Find the specific debtor
    $debtor = Debtor::where('id', $id)->where('usr_id', Auth::id())->first();

    // Check if debtor exists
    if (!$debtor) {
        return response()->json([
            'success' => false,
            'message' => 'Debtor not found.',
        ], 404);
    }

    // Get the payment amount
    $paymentAmount = $request->input('payment_amount');

    // Ensure payment doesn't exceed total amount
    if ($paymentAmount > $debtor->total_amount) {
        return response()->json([
            'success' => false,
            'message' => 'Payment amount cannot exceed total debt.',
        ], 400);
    }

    // Check if payment is before due date
    $isDueDateEarly = now()->lessThan($debtor->due_date);

    // Find associated debtor contact
    $debtorContact = DebtorContact::where('email', $debtor->email)
        ->where('usr_id', Auth::id())
        ->first();

    // Deduct payment from total amount
    $debtor->total_amount -= $paymentAmount;

    // If total amount is zero or less, mark as paid
    if ($debtor->total_amount <= 0) {
        $debtor->is_paid = true;
        $debtor->total_amount = 0;
    }

    // Save the updated debtor record
    $debtor->save();

    // Update credit score if paid early
    if ($isDueDateEarly && $debtorContact) {
        // Ensure credit score doesn't exceed 100
        $debtorContact->credit_score = min(100, $debtorContact->credit_score + 2);
        $debtorContact->save();
    }

    // Send confirmation email
    try {
        Mail::to($debtor->email)->send(new PaymentConfirmationMail($debtor, $paymentAmount));
    } catch (\Exception $e) {
        // Log email sending failure but don't interrupt the payment process
        \Log::error('Failed to send payment confirmation email: ' . $e->getMessage());
    }

    return response()->json([
        'success' => true,
        'message' => 'Payment recorded successfully.',
        'data' => [
            'debtor' => $debtor,
            'payment_amount' => $paymentAmount,
            'remaining_balance' => $debtor->total_amount,
            'credit_score_updated' => $isDueDateEarly ? 'Yes' : 'No'
        ]
    ]);
}
}


<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Debtor;
use Illuminate\Http\Request;

class DebtorController extends Controller
{
    /**
     * Generate a unique 5-digit debt ID.
     */
    protected function generateDebtId()
    {
        do {
            $debtId = random_int(10000, 99999); // Generate a random 5-digit number
        } while (Debtor::where('debt_id', $debtId)->exists());

        return $debtId;
    }

    /**
     * Store a new debtor.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:15',
            'email' => 'required|email|unique:debtors',
            'amount_to_borrow' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'due_date' => 'required|date',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'debt_type' => 'required|in:receivable,payable'
        ]);

        // Generate a unique debt ID
        

        // Create a new debtor record
        $debtor = Debtor::create(array_merge($request->all()));

        // Return a success response
        return response()->json([
            'success' => true,
            'message' => 'Debtor information stored successfully.',
            'data' => $debtor,
        ], 201);
    }

    /**
     * Display a list of debtors.
     */
    public function index(Request $request)
{
    $isArchived = $request->query('is_archived', false); // Default: show active
    $debtors = Debtor::where('is_archived', $isArchived)->get();

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

    /**
 * Update a debtor's information.
 */
public function update(Request $request, $id)
{
    // Validate the incoming request
    $request->validate([
        'name' => 'nullable|string|max:255',
        'contact_number' => 'nullable|string|max:15',
        'email' => 'nullable|email|unique:debtors,email,' . $id, // Ensure email is unique except for this debtor
        'amount_to_borrow' => 'nullable|numeric|min:0',
        'duration_of_payment' => 'nullable|integer|min:1',
        'interest_rate' => 'nullable|numeric|min:0|max:100',
    ]);

    // Find the debtor by ID
    $debtor = Debtor::find($id);

    // Check if the debtor exists
    if (!$debtor) {
        return response()->json([
            'success' => false,
            'message' => 'Debtor not found.',
        ], 404);
    }

    // Update the debtor's information
    $debtor->update($request->all());

    // Return a success response
    return response()->json([
        'success' => true,
        'message' => 'Debtor information updated successfully.',
        'data' => $debtor,
    ]);
}
/**
 * Archive or unarchive a debtor.
 */
public function archive($id)
{
    // Find the debtor by ID
    $debtor = Debtor::find($id);

    // Check if the debtor exists
    if (!$debtor) {
        return response()->json([
            'success' => false,
            'message' => 'Debtor not found.',
        ], 404);
    }

    // Toggle the `is_archived` status
    $debtor->is_archived = !$debtor->is_archived;
    $debtor->save();

    // Return a success response
    return response()->json([
        'success' => true,
        'message' => $debtor->is_archived ? 'Debtor archived successfully.' : 'Debtor unarchived successfully.',
        'data' => $debtor,
    ]);
}
/**
 * Display the specified debtor.
 */
public function show($id)
{
    $debtor = Debtor::find($id);

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
/**
 * Get a list of receivable debtors.
 */
/**
 * Get a list of receivable debtors that are not archived.
 */
public function receivables()
{
    $debtors = Debtor::where('debt_type', 'receivable')
                     ->where('is_archived', false) // Exclude archived debtors
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


/**
 * Get a list of payable debtors.
 */
public function payables()
{
    $debtors = Debtor::where('debt_type', 'payable')
                     ->where('is_archived', false) // Exclude archived debtors
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



}
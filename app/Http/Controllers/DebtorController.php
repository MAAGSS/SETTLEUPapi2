<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Debtor;
use Illuminate\Http\Request;

class DebtorController extends Controller
{

    protected function generateDebtId()
    {
        do {
            $debtId = random_int(10000, 99999); // Generate a random 5-digit number
        } while (Debtor::where('debt_id', $debtId)->exists());

        return $debtId;
    }


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

        $debtor = Debtor::create(array_merge($request->all()));


        return response()->json([
            'success' => true,
            'message' => 'Debtor information stored successfully.',
            'data' => $debtor,
        ], 201);
    }


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


    $debtor = Debtor::find($id);


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

    $debtor = Debtor::find($id);


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
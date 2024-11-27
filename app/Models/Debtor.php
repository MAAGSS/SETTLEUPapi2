<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Debtor extends Model
{
    use HasFactory;

    protected $fillable = [
        'debt_id',
        'name',
        'contact_number',
        'email',
        'amount_to_borrow',
        'start_date',
        'due_date',
        'interest_rate',
        'is_archived',
        'debt_type', // New column
    ];
    
    /**
     * Get the total amount including interest.
     *
     * @return float
     */
    public function getTotalAmountAttribute()
    {
        $interest = ($this->amount_to_borrow * $this->interest_rate) / 100;
        return $this->amount_to_borrow + $interest;
    }
}

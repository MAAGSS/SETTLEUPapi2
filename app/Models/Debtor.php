<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Debtor extends Model
{
    use HasFactory;

    protected $fillable = [
        'debt_id',
        'usr_id',
        'name',
        'contact_number',
        'email',
        'amount_to_borrow',
        'start_date',
        'due_date',
        'interest_rate',
        'is_archived',
        'debt_type',
        'total_amount',  // Explicitly allow mass assignment
        'is_paid'
    ];

    // Accessor for total amount
    public function getTotalAmountAttribute()
    {
        // If total_amount is already set in the database, return it
        if (isset($this->attributes['total_amount']) && $this->attributes['total_amount'] > 0) {
            return $this->attributes['total_amount'];
        }

        // Calculate total amount with interest
        $interest = ($this->amount_to_borrow * $this->interest_rate) / 100;
        return $this->amount_to_borrow + $interest;
    }

    // Mutator to ensure total amount is set when creating/updating
    public function setTotalAmountAttribute($value)
    {
        $this->attributes['total_amount'] = $value ?? 
            $this->amount_to_borrow + ($this->amount_to_borrow * $this->interest_rate / 100);
    }

    // Boot method to set total amount on creation
    protected static function booted()
    {
        static::creating(function ($debtor) {
            $interest = ($debtor->amount_to_borrow * $debtor->interest_rate) / 100;
            $debtor->total_amount = $debtor->amount_to_borrow + $interest;
        });
    }
}
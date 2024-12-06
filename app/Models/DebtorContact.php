<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebtorContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_number',
        'email',
        'credit_score',
        'usr_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'usr_id');
    }
}


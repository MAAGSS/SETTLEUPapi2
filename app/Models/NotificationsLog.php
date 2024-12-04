<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationsLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'contact_number',
        'email',
        'message',
        'type',
        'status',
        'error_message',
    ];

    // Define the relationship with the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

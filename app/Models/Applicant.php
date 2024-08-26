<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Applicant extends Model
{
    use HasFactory;
    protected $fillable = [
        'car_id',
        'user_id',
        'purpose',
        'submission_date',
        'expiry_date',
        'status',
        'accepted_at',
        'denied_at',
        'delete_admin',
        'delete_user',
        'notes',
    ];


    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    // Define the relationship with the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function getImageAttribute($value)
        {
            return env('APP_URL') . $value;
        }
}

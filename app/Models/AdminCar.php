<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminCar extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'car_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mendapatkan mobil yang ditugaskan kepada admin ini.
     */
    public function car()
    {
        return $this->belongsTo(Car::class);
    }
}

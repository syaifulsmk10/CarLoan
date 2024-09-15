<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;
    protected $fillable = [
        'status',
        'name_car',
        'path',
    ];

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }

    public function adminCars()
    {
        return $this->hasMany(AdminCar::class);
    }
    
    public function getImageAttribute($value)
        {
            return env('APP_URL') . $value;
        }

    // public function status()
    // {
    //     return $this->belongsTo(Status::class);
    // }
}

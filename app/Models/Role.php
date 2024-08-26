<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    
    protected $fillable = ['name'];

    // Define the relationship with the User model
    public function users()
    {
        return $this->hasMany(User::class);
    }
    
    public function getImageAttribute($value)
        {
            return env('APP_URL') . $value;
        }
}

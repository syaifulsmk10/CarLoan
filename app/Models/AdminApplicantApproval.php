<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminApplicantApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'applicant_id',
        "approval_status",
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mendapatkan pengajuan yang di-review oleh admin ini.
     */
    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
     
}

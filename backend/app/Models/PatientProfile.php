<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientProfile extends Model
{
    protected $fillable = [
        'user_id',
        'mrn',
        'cnic',
        'blood_group',
        'gender',
        'dob',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'allergies',
        'medical_history_notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

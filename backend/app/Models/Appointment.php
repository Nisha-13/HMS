<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'appointment_no',
        'patient_id',
        'cnic',
        'doctor_id',
        'department_id',
        'appointment_date',
        'time_slot',
        'token_no',
        'status',
        'type',
        'symptoms',
        'patient_notes',
        'fee',
        'payment_status',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function prescription()
    {
        return $this->hasOne(Prescription::class);
    }

    public function opdQueue()
    {
        return $this->hasOne(OpdQueue::class);
    }
}

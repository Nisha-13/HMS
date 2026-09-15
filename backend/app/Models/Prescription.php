<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    protected $fillable = [
        'prescription_no',
        'appointment_id',
        'doctor_id',
        'patient_id',
        'diagnosis',
        'symptoms_summary',
        'bp',
        'pulse',
        'temperature',
        'weight',
        'advice_notes',
        'follow_up_date',
    ];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function items()
    {
        return $this->hasMany(PrescriptionItem::class);
    }
}

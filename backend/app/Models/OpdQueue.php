<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpdQueue extends Model
{
    protected $table = 'opd_queues';

    protected $fillable = [
        'token_number',
        'doctor_id',
        'department_id',
        'patient_id',
        'cnic',
        'patient_name',
        'patient_phone',
        'appointment_id',
        'counter_no',
        'status',
        'queue_order',
        'called_at',
    ];

    protected $casts = [
        'called_at' => 'datetime',
    ];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}

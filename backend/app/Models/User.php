<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'avatar',
        'status',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function doctorProfile()
    {
        return $this->hasOne(DoctorProfile::class);
    }

    public function patientProfile()
    {
        return $this->hasOne(PatientProfile::class);
    }

    public function doctorAppointments()
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    public function patientAppointments()
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function doctorPrescriptions()
    {
        return $this->hasMany(Prescription::class, 'doctor_id');
    }

    public function patientPrescriptions()
    {
        return $this->hasMany(Prescription::class, 'patient_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'patient_id');
    }
}

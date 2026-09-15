<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'image',
        'head_doctor_name',
        'room_floor',
        'is_active',
    ];

    public function doctors()
    {
        return $this->hasMany(DoctorProfile::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}

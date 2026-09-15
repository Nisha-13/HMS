<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorProfile extends Model
{
    protected $fillable = [
        'user_id',
        'department_id',
        'specialty',
        'qualification',
        'experience_years',
        'consultation_fee',
        'room_no',
        'bio',
        'available_days',
        'available_time_start',
        'available_time_end',
        'is_available',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}

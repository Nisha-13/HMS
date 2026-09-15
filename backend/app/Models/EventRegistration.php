<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'pass_code',
        'event_id',
        'user_id',
        'attendee_name',
        'attendee_phone',
        'cnic_id',
        'age_group',
        'health_concern',
        'attendee_email',
        'attendees_count',
        'status',
    ];

    public function event()
    {
        return $this->belongsTo(HospitalEvent::class, 'event_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

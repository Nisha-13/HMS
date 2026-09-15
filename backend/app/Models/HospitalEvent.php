<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalEvent extends Model
{
    protected $table = 'hospital_events';

    protected $fillable = [
        'title',
        'slug',
        'category',
        'event_date',
        'time_slot',
        'location',
        'description',
        'image',
        'organizer',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];
}

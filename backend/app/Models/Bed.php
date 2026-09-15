<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bed extends Model
{
    protected $fillable = [
        'ward_id',
        'bed_number',
        'is_occupied',
        'patient_id',
        'patient_name',
        'admitted_at',
    ];

    protected $casts = [
        'is_occupied' => 'boolean',
        'admitted_at' => 'datetime',
    ];

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = [
        'patient_name',
        'patient_role',
        'department_name',
        'comment',
        'rating',
        'avatar',
    ];
}

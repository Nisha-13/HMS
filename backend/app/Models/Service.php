<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'icon',
        'image',
        'starting_price',
        'department_name',
        'is_featured',
    ];
}

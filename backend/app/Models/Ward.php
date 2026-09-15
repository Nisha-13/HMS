<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    protected $fillable = [
        'name',
        'floor',
        'type',
        'total_beds',
        'daily_rate',
    ];

    public function beds()
    {
        return $this->hasMany(Bed::class);
    }
}

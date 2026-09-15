<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_no',
        'patient_id',
        'appointment_id',
        'subtotal',
        'tax',
        'discount',
        'total_amount',
        'paid_amount',
        'payment_status',
        'payment_method',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}

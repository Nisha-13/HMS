<?php

namespace App\Listeners;

use App\Events\AppointmentBookedEvent;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyAppointmentPartiesListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AppointmentBookedEvent $event): void
    {
        $appointment = $event->appointment;
        
        Log::info("Hospital Event Triggered: Appointment #{$appointment->appointment_no} booked for patient {$appointment->patient_id} with doctor {$appointment->doctor_id}");

        // Record in Audit Log
        AuditLog::create([
            'user_id' => $appointment->patient_id,
            'action' => 'APPOINTMENT_BOOKED',
            'entity_type' => 'Appointment',
            'entity_id' => $appointment->id,
            'payload' => [
                'appointment_no' => $appointment->appointment_no,
                'doctor_id' => $appointment->doctor_id,
                'date' => $appointment->appointment_date,
                'time' => $appointment->time_slot,
            ],
            'ip_address' => request()->ip() ?? '127.0.0.1',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\OpdQueue;
use App\Models\User;
use App\Models\Prescription;
use App\Events\QueueTokenCalledEvent;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function getDoctorDashboard(Request $request)
    {
        $doctor = $request->user();
        $today = date('Y-m-d');

        $todayAppointments = Appointment::with(['patient.patientProfile', 'department', 'prescription.items', 'opdQueue'])
            ->where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $today)
            ->orderBy('id', 'asc')
            ->get();

        $activeQueue = OpdQueue::where('doctor_id', $doctor->id)
            ->whereDate('created_at', $today)
            ->where('status', '!=', 'pending_checkin')
            ->orderBy('queue_order', 'asc')
            ->get();

        $totalConsultations = Prescription::where('doctor_id', $doctor->id)->count();

        // Count from activeQueue (checked-in patients) so numbers match the left sidebar tokens exactly:
        $totalQueueCount = $activeQueue->count();
        $waitingCount = $activeQueue->where('status', 'waiting')->count();
        $completedCount = $activeQueue->where('status', 'completed')->count();

        return response()->json([
            'success' => true,
            'doctor' => $doctor->load('doctorProfile.department'),
            'today_appointments' => $todayAppointments,
            'active_queue' => $activeQueue,
            'stats' => [
                'today_count' => $totalQueueCount,
                'waiting_count' => $waitingCount,
                'completed_count' => $completedCount,
                'total_consultations' => $totalConsultations,
            ]
        ]);
    }

    public function callPatient(Request $request)
    {
        $request->validate([
            'queue_id' => 'required|exists:opd_queues,id',
        ]);

        $doctor = $request->user();

        // 1. Fetch patient queue and check status
        $queue = OpdQueue::with(['patient', 'appointment', 'department'])->findOrFail($request->queue_id);

        if ($queue->status === 'pending_checkin') {
            return response()->json([
                'success' => false,
                'message' => "Patient {$queue->patient_name} has not checked in or paid consultation fee at Central Reception yet."
            ], 422);
        }

        // 2. Mark any other patient previously being called by this doctor as 'completed'
        OpdQueue::where('doctor_id', $doctor->id)
            ->where('status', 'calling')
            ->where('id', '!=', $request->queue_id)
            ->update(['status' => 'completed']);

        // 3. Set new patient as 'calling'
        $queue->status = 'calling';
        $queue->called_at = now();
        $queue->save();


        if ($queue->appointment) {
            $queue->appointment->status = 'in_consultation';
            $queue->appointment->save();
        }

        // Fire real-time event for patient calling display & sound alerts
        event(new QueueTokenCalledEvent($queue));

        return response()->json([
            'success' => true,
            'message' => "Patient {$queue->patient_name} (Token {$queue->token_number}) is being called to {$queue->counter_no}.",
            'queue' => $queue
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Appointment;
use App\Models\OpdQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrescriptionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Prescription::with(['doctor.doctorProfile.department', 'patient.patientProfile', 'appointment', 'items']);

        if ($user->role === 'patient') {
            $query->where('patient_id', $user->id);
        } elseif ($user->role === 'doctor') {
            $query->where('doctor_id', $user->id);
        }

        $prescriptions = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'prescriptions' => $prescriptions
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'nullable|exists:users,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'queue_id' => 'nullable|exists:opd_queues,id',
            'diagnosis' => 'required|string',
            'symptoms_summary' => 'nullable|string',
            'bp' => 'nullable|string',
            'pulse' => 'nullable|string',
            'temperature' => 'nullable|string',
            'weight' => 'nullable|string',
            'advice_notes' => 'nullable|string',
            'follow_up_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.medicine_name' => 'required|string',
            'items.*.dosage' => 'required|string',
            'items.*.frequency' => 'required|string',
            'items.*.duration' => 'required|string',
            'items.*.instruction' => 'nullable|string',
        ]);

        $doctor = $request->user();

        // Resolve patient ID
        $patientId = $request->patient_id;
        if (!$patientId && $request->appointment_id) {
            $apt = Appointment::find($request->appointment_id);
            if ($apt) $patientId = $apt->patient_id;
        }

        // If patientId is still null (e.g. walk-in), fallback to first patient user
        if (!$patientId) {
            $patientId = \App\Models\User::where('role', 'patient')->first()?->id ?? $doctor->id;
        }

        $prescription = Prescription::create([
            'prescription_no' => 'RX-' . strtoupper(Str::random(6)),
            'appointment_id' => $request->appointment_id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patientId,
            'diagnosis' => $request->diagnosis,
            'symptoms_summary' => $request->symptoms_summary,
            'bp' => $request->bp,
            'pulse' => $request->pulse,
            'temperature' => $request->temperature,
            'weight' => $request->weight,
            'advice_notes' => $request->advice_notes,
            'follow_up_date' => $request->follow_up_date,
        ]);

        foreach ($request->items as $item) {
            PrescriptionItem::create([
                'prescription_id' => $prescription->id,
                'medicine_name' => $item['medicine_name'],
                'dosage' => $item['dosage'] ?? '1 tablet',
                'frequency' => $item['frequency'] ?? '1-0-1',
                'duration' => $item['duration'] ?? '5 days',
                'instruction' => $item['instruction'] ?? 'After food',
            ]);
        }

        // 1. Mark Appointment as completed if exists
        if ($request->appointment_id) {
            $apt = Appointment::find($request->appointment_id);
            if ($apt) {
                $apt->status = 'completed';
                $apt->save();

                $queue = OpdQueue::where('appointment_id', $apt->id)->first();
                if ($queue) {
                    $queue->status = 'completed';
                    $queue->save();
                }
            }
        }

        // 2. Mark OPD Queue entry as completed if queue_id is passed
        if ($request->queue_id) {
            $queue = OpdQueue::find($request->queue_id);
            if ($queue) {
                $queue->status = 'completed';
                $queue->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Digital Prescription and Medical Record saved successfully!',
            'prescription' => $prescription->load(['doctor.doctorProfile.department', 'patient', 'items'])
        ], 201);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpdQueue;
use App\Models\User;
use App\Models\Department;
use App\Events\QueueTokenCalledEvent;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function getLiveQueue(Request $request)
    {
        $today = date('Y-m-d');
        
        // Only fetch TODAY's queue records — never show old/previous day tokens
        $query = OpdQueue::with([
            'doctor.doctorProfile.department', 
            'department', 
            'patient'
        ])->whereDate('created_at', $today);

        if ($request->has('department_id') && $request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('doctor_id') && $request->doctor_id) {
            $query->where('doctor_id', $request->doctor_id);
        }

        $queues = $query->orderBy('queue_order', 'asc')->get();

        $currentlyCalling = $queues->where('status', 'calling')->sortByDesc('called_at')->values();
        $waitingList      = $queues->where('status', 'waiting')->sortBy('queue_order')->values();
        $completedList    = $queues->where('status', 'completed')->sortByDesc('updated_at')->take(20)->values();
        $activeCount      = $queues->where('status', '!=', 'pending_checkin')->count();

        return response()->json([
            'success'               => true,
            'timestamp'             => now()->toIso8601String(),
            'currently_calling'     => $currentlyCalling,
            'waiting_list'          => $waitingList,
            'completed_list'        => $completedList,
            'total_today'           => $activeCount,
            'pending_checkin_count' => $queues->where('status', 'pending_checkin')->count(),
            'departments'           => Department::where('is_active', true)->select('id', 'name', 'slug')->get(),
        ]);

    }

    public function generateWalkInToken(Request $request)
    {
        $request->validate([
            'patient_name' => 'required|string',
            'patient_phone' => 'nullable|string',
            'doctor_id' => 'required|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $doctor = User::with('doctorProfile')->findOrFail($request->doctor_id);
        $today = date('Y-m-d');

        // Prevent duplicate walk-in token if patient is already active in queue for this doctor today
        $existingQueue = OpdQueue::where('doctor_id', $doctor->id)
            ->whereDate('created_at', $today)
            ->where('patient_name', $request->patient_name)
            ->whereIn('status', ['waiting', 'calling', 'in_room'])
            ->first();

        if ($existingQueue) {
            $docDisplayName = preg_match('/^dr\.?\s+/i', $doctor->name) ? $doctor->name : "Dr. {$doctor->name}";
            return response()->json([
                'success' => false,
                'message' => "Token #{$existingQueue->token_number} is already active for {$request->patient_name} with {$docDisplayName} today."
            ], 422);
        }

        $todayCount = OpdQueue::whereDate('created_at', $today)->count();
        $nextNumber = $todayCount + 1;
        do {
            $tokenNo = 'W-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $exists = OpdQueue::whereDate('created_at', $today)->where('token_number', $tokenNo)->exists();
            if ($exists) {
                $nextNumber++;
            }
        } while ($exists);

        $queue = OpdQueue::create([
            'token_number' => $tokenNo,
            'doctor_id' => $doctor->id,
            'department_id' => $request->department_id ?? ($doctor->doctorProfile ? $doctor->doctorProfile->department_id : null),
            'patient_name' => $request->patient_name,
            'patient_phone' => $request->patient_phone,
            'counter_no' => $doctor->doctorProfile ? $doctor->doctorProfile->room_no : 'OPD Room 101',
            'status' => 'waiting',
            'queue_order' => $nextNumber,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Walk-in Token #{$tokenNo} generated successfully for {$request->patient_name}.",
            'token' => $queue->load(['doctor.doctorProfile', 'department'])
        ], 201);
    }
}

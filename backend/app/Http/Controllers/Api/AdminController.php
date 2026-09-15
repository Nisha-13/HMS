<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Bed;
use App\Models\OpdQueue;
use App\Models\AuditLog;
use App\Models\DoctorProfile;
use App\Models\Service;
use App\Models\HospitalEvent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function getAnalytics()
    {
        $today = date('Y-m-d');
        $totalDoctors = User::where('role', 'doctor')->count();
        $totalPatients = User::where('role', 'patient')->count();
        $todayAppointments = Appointment::whereDate('appointment_date', $today)->count();
        $totalRevenue = Invoice::where('payment_status', 'paid')->sum('paid_amount');
        $totalBeds = Bed::count();
        $occupiedBeds = Bed::where('is_occupied', true)->count();
        $liveQueueCount = OpdQueue::whereDate('created_at', $today)
            ->where('status', 'waiting')
            ->count();

        $recentAppointments = Appointment::with([
            'patient',
            'doctor.doctorProfile.department',
            'department'
        ])
            ->orderBy('id', 'desc')
            ->take(6)
            ->get();

        $recentAuditLogs = AuditLog::with('user')
            ->orderBy('id', 'desc')
            ->take(8)
            ->get();

        return response()->json([
            'success' => true,
            'kpis' => [
                'total_doctors' => $totalDoctors,
                'total_patients' => $totalPatients,
                'today_appointments' => $todayAppointments,
                'total_revenue' => (float) $totalRevenue,
                'total_beds' => $totalBeds,
                'occupied_beds' => $occupiedBeds,
                'bed_occupancy_rate' => $totalBeds > 0
                    ? round(($occupiedBeds / $totalBeds) * 100, 1)
                    : 0,
                'live_queue_waiting' => $liveQueueCount,
            ],
            'recent_appointments' => $recentAppointments,
            'audit_logs' => $recentAuditLogs,
        ]);
    }

    public function getAuditLogs()
    {
        $logs = AuditLog::with('user')
            ->orderBy('id', 'desc')
            ->take(50)
            ->get();

        return response()->json([
            'success' => true,
            'logs' => $logs
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Doctor Management
    |--------------------------------------------------------------------------
    */

    public function createDoctor(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
            'specialty' => 'nullable|string',
            'qualification' => 'required|string',
            'experience_years' => 'required|numeric|min:0',
            'consultation_fee' => 'required|numeric|min:0',
            'room_no' => 'required|string',
            'bio' => 'nullable|string',
            'avatar' => 'nullable|string',
        ]);

        // Default doctor avatar
        $avatarPath = 'assets/images/doc1.jpg';

        // Handle custom doctor avatar upload
        if ($request->filled('avatar')) {
            if (str_starts_with($request->avatar, 'data:image/')) {
                @list($type, $data) = explode(';', $request->avatar);
                @list(, $data) = explode(',', $data);

                if ($data) {
                    $decoded = base64_decode($data);

                    $ext = str_contains($type, 'png')
                        ? 'png'
                        : (str_contains($type, 'webp') ? 'webp' : 'jpg');

                    $fileName = 'doctor_' . time() . '_' . Str::random(6) . '.' . $ext;

                    Storage::disk('public')->put(
                        'avatars/' . $fileName,
                        $decoded
                    );

                    $avatarPath = 'storage/avatars/' . $fileName;
                }
            } else {
                $avatarPath = ltrim($request->avatar, '/');
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => 'doctor',
            'status' => 'active',
            'password' => Hash::make($request->password),
            'avatar' => $avatarPath,
        ]);

        $profile = DoctorProfile::create([
            'user_id' => $user->id,
            'department_id' => $request->department_id,
            'specialty' => $request->specialty ?? $request->qualification,
            'qualification' => $request->qualification,
            'experience_years' => $request->experience_years,
            'consultation_fee' => $request->consultation_fee,
            'room_no' => $request->room_no,
            'bio' => $request->bio,
            'available_days' => 'Mon,Tue,Wed,Thu,Fri',
            'available_time_start' => '09:00:00',
            'available_time_end' => '17:00:00',
            'is_available' => true,
        ]);

        AuditLog::create([
            'user_id' => $request->user()?->id ?? $user->id,
            'action' => 'DOCTOR_APPOINTED',
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'payload' => [
                'name' => $user->name,
                'email' => $user->email,
                'room' => $request->room_no
            ],
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Dr. {$user->name} successfully registered to clinical faculty.",
            'doctor' => $user->load('doctorProfile.department')
        ], 201);
    }

    public function deleteDoctor(Request $request, $id)
    {
        $doctor = User::where('role', 'doctor')->findOrFail($id);
        $name = $doctor->name;

        // Remove custom uploaded avatar
        if ($doctor->avatar && str_starts_with($doctor->avatar, 'storage/avatars/')) {
            Storage::disk('public')->delete(
                str_replace('storage/', '', $doctor->avatar)
            );
        }

        DoctorProfile::where('user_id', $doctor->id)->delete();
        $doctor->delete();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'DOCTOR_REMOVED',
            'entity_type' => 'User',
            'entity_id' => $id,
            'payload' => [
                'name' => $name
            ],
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Dr. {$name} removed from clinical faculty."
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Department Management
    |--------------------------------------------------------------------------
    */

    public function createDepartment(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'description' => 'required|string',
            'icon' => 'nullable|string',
            'head_doctor_name' => 'nullable|string',
            'room_floor' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        $slug = Str::slug($request->name);
        $randomDepImg = 'assets/images/dep' . rand(1, 8) . '.jpg';

        $department = Department::create([
            'name' => $request->name,
            'slug' => $slug,
            'icon' => $request->icon ?? 'Activity',
            'description' => $request->description,
            'image' => $request->image ?: $randomDepImg,
            'head_doctor_name' => $request->head_doctor_name ?? 'Senior Consultant',
            'room_floor' => $request->room_floor ?? 'Ground Floor',
            'is_active' => true,
        ]);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'DEPARTMENT_CREATED',
            'entity_type' => 'Department',
            'entity_id' => $department->id,
            'payload' => [
                'name' => $department->name
            ],
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Department '{$department->name}' created successfully.",
            'department' => $department
        ], 201);
    }

    public function deleteDepartment(Request $request, $id)
    {
        $department = Department::findOrFail($id);
        $name = $department->name;

        $department->delete();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'DEPARTMENT_DELETED',
            'entity_type' => 'Department',
            'entity_id' => $id,
            'payload' => [
                'name' => $name
            ],
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Department '{$name}' deleted successfully."
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Service Management
    |--------------------------------------------------------------------------
    */

    public function createService(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'starting_price' => 'required|numeric|min:0',
            'department_name' => 'nullable|string',
            'icon' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        $randomServiceImg = 'assets/images/service' . rand(1, 8) . '.jpg';

        $service = Service::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'description' => $request->description,
            'icon' => $request->icon ?? 'Sparkles',
            'image' => $request->image ?: $randomServiceImg,
            'starting_price' => $request->starting_price,
            'department_name' => $request->department_name ?? 'General Diagnostic',
            'is_featured' => true,
        ]);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'SERVICE_CREATED',
            'entity_type' => 'Service',
            'entity_id' => $service->id,
            'payload' => [
                'title' => $service->title,
                'price' => $service->starting_price
            ],
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Service '{$service->title}' created successfully.",
            'service' => $service
        ], 201);
    }

    public function deleteService(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        $title = $service->title;

        $service->delete();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'SERVICE_CREATED',
            'entity_type' => 'Service',
            'entity_id' => $id,
            'payload' => [
                'title' => $title
            ],
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Service '{$title}' removed successfully."
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Hospital Events & Camps Management
    |--------------------------------------------------------------------------
    */

    public function createEvent(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'event_date' => 'required|date',
            'time_slot' => 'required|string',
            'location' => 'required|string',
            'description' => 'required|string',
            'organizer' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        $randomEventImg = 'assets/images/Event' . rand(1, 8) . '.jpg';

        $event = HospitalEvent::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'category' => $request->category,
            'event_date' => $request->event_date,
            'time_slot' => $request->time_slot,
            'location' => $request->location,
            'description' => $request->description,
            'image' => $request->image ?: $randomEventImg,
            'organizer' => $request->organizer ?? 'HealthPulse Outreach Team',
        ]);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'EVENT_CREATED',
            'entity_type' => 'HospitalEvent',
            'entity_id' => $event->id,
            'payload' => [
                'title' => $event->title,
                'date' => $event->event_date
            ],
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Health Event '{$event->title}' scheduled successfully.",
            'event' => $event
        ], 201);
    }

    public function deleteEvent(Request $request, $id)
    {
        $event = HospitalEvent::findOrFail($id);
        $title = $event->title;

        $event->delete();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'EVENT_DELETED',
            'entity_type' => 'HospitalEvent',
            'entity_id' => $id,
            'payload' => [
                'title' => $title
            ],
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Event '{$title}' removed successfully."
        ]);
    }
}
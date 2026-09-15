<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DoctorProfile;
use App\Models\Service;
use App\Models\HospitalEvent;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Bed;
use App\Models\EventRegistration;
use App\Models\PatientProfile;
use App\Services\PatientVerificationService;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function getDepartments()
    {
        $departments = Department::withCount('doctors')->where('is_active', true)->get();
        return response()->json([
            'success' => true,
            'departments' => $departments
        ]);
    }

    public function getDoctors(Request $request)
    {
        $query = User::where('role', 'doctor')
            ->where('status', 'active')
            ->with(['doctorProfile.department']);

        if ($request->has('department_id') && $request->department_id) {
            $query->whereHas('doctorProfile', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        $doctors = $query->get();

        return response()->json([
            'success' => true,
            'doctors' => $doctors
        ]);
    }

    public function getServices()
    {
        $services = Service::where('is_featured', true)->get();
        return response()->json([
            'success' => true,
            'services' => $services
        ]);
    }

    public function getEvents()
    {
        $events = HospitalEvent::orderBy('event_date', 'asc')->get();
        return response()->json([
            'success' => true,
            'events' => $events
        ]);
    }

    public function getTestimonials()
    {
        $testimonials = Testimonial::orderBy('rating', 'desc')->get();
        return response()->json([
            'success' => true,
            'testimonials' => $testimonials
        ]);
    }

    public function getHospitalStats()
    {
        $doctorsCount = User::where('role', 'doctor')->count();
        $departmentsCount = Department::count();
        $totalBeds = Bed::count();
        $occupiedBeds = Bed::where('is_occupied', true)->count();
        $totalAppointments = Appointment::count();

        return response()->json([
            'success' => true,
            'stats' => [
                'doctors' => $doctorsCount ?: 45,
                'departments' => $departmentsCount ?: 12,
                'beds' => $totalBeds ?: 160,
                'bed_occupancy_rate' => $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 1) : 74.5,
                'appointments_handled' => $totalAppointments + 5400,
                'patient_satisfaction' => '99.4%',
                'emergency_response_mins' => '3 mins',
            ]
        ]);
    }

    public function registerEvent(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:hospital_events,id',
            'attendee_name' => 'required|string|max:120',
            'attendee_phone' => 'required|string',
            'cnic_id' => 'required|string',
            'age_group' => 'required|string',
            'health_concern' => 'required|string',
            'attendee_email' => 'nullable|email',
            'attendees_count' => 'nullable|integer|min:1|max:5',
        ]);

        // 1. Strict Identity Verification: CNIC, Phone, Disposable Email
        $cnicCheck = PatientVerificationService::validateCnic($request->cnic_id);
        if (!$cnicCheck['valid']) {
            return response()->json([
                'success' => false,
                'message' => $cnicCheck['error'],
            ], 422);
        }
        $normalizedCnic = $cnicCheck['normalized'];

        $phoneCheck = PatientVerificationService::validatePhone($request->attendee_phone);
        if (!$phoneCheck['valid']) {
            return response()->json([
                'success' => false,
                'message' => $phoneCheck['error'],
            ], 422);
        }
        $normalizedPhone = $phoneCheck['normalized'];

        if ($request->attendee_email) {
            $emailCheck = PatientVerificationService::validateEmail($request->attendee_email);
            if (!$emailCheck['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $emailCheck['error'],
                ], 422);
            }
        }

        $event = HospitalEvent::findOrFail($request->event_id);

        // 2. Resolve Authenticated User (Sanctum)
        $currentUser = $request->user('sanctum');
        $userId = $currentUser ? $currentUser->id : null;

        // If guest, try to match existing registered patient profile by CNIC or email
        if (!$userId) {
            $patientProfile = PatientProfile::where('cnic', $normalizedCnic)->first();
            if ($patientProfile) {
                $userId = $patientProfile->user_id;
            } else if ($request->attendee_email) {
                $matchedUser = User::where('email', $request->attendee_email)->first();
                if ($matchedUser) {
                    $userId = $matchedUser->id;
                }
            }
        } else if ($currentUser->role === 'patient') {
            // Update patient profile CNIC if not previously set
            $profile = $currentUser->patientProfile;
            if ($profile && empty($profile->cnic)) {
                $profile->cnic = $normalizedCnic;
                $profile->save();
            }
        }

        // 3. Duplicate Registration Guard: Prevent duplicate entry passes for the SAME event
        $existing = EventRegistration::where('event_id', $event->id)
            ->where(function ($q) use ($normalizedCnic, $normalizedPhone, $userId) {
                $q->where('cnic_id', $normalizedCnic)
                  ->orWhere('attendee_phone', $normalizedPhone);
                if ($userId) {
                    $q->orWhere('user_id', $userId);
                }
            })
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => "You have already registered for '{$event->title}' with Pass Code #{$existing->pass_code}. Each attendee is limited to one entry pass per medical camp.",
                'existing_pass_code' => $existing->pass_code,
            ], 422);
        }

        $passCode = 'CMP-' . strtoupper(\Illuminate\Support\Str::random(6));

        $reg = EventRegistration::create([
            'pass_code' => $passCode,
            'event_id' => $event->id,
            'user_id' => $userId,
            'attendee_name' => $request->attendee_name,
            'attendee_phone' => $normalizedPhone,
            'cnic_id' => $normalizedCnic,
            'age_group' => $request->age_group,
            'health_concern' => $request->health_concern,
            'attendee_email' => $request->attendee_email ?: ($currentUser ? $currentUser->email : null),
            'attendees_count' => $request->attendees_count ?: 1,
            'status' => 'confirmed',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Free Camp Registration Confirmed! Please present this verified digital pass at the entrance.',
            'registration' => [
                'id' => $reg->id,
                'pass_code' => $passCode,
                'event_title' => $event->title,
                'event_date' => $event->event_date,
                'time_slot' => $event->time_slot,
                'location' => $event->location,
                'organizer' => $event->organizer,
                'attendee_name' => $request->attendee_name,
                'attendee_phone' => $normalizedPhone,
                'cnic_id' => $normalizedCnic,
                'age_group' => $request->age_group,
                'health_concern' => $request->health_concern,
                'attendees_count' => $request->attendees_count ?: 1,
                'verified_quota' => $userId ? 'Verified Patient Quota' : 'Community Subsidized Quota',
                'is_authenticated' => (bool) $userId,
                'fee' => 'FREE ($0.00)'
            ]
        ]);
    }

    public function getPatientEventRegistrations(Request $request)
    {
        $user = $request->user();
        $userCnic = $user->patientProfile?->cnic;

        $registrations = EventRegistration::with('event')
            ->where(function ($q) use ($user, $userCnic) {
                $q->where('user_id', $user->id);
                if ($userCnic) {
                    $q->orWhere('cnic_id', $userCnic);
                }
                if ($user->email) {
                    $q->orWhere('attendee_email', $user->email);
                }
            })
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'registrations' => $registrations
        ]);
    }

    public function getEventRegistrations()
    {
        $registrations = \App\Models\EventRegistration::with('event')->orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'registrations' => $registrations
        ]);
    }

    public function updateEventRegistrationStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:confirmed,completed,attended,cancelled'
        ]);

        $reg = \App\Models\EventRegistration::findOrFail($id);
        $reg->status = $request->status;
        $reg->save();

        return response()->json([
            'success' => true,
            'message' => "Registration status updated to {$reg->status}.",
            'registration' => $reg->load('event')
        ]);
    }

    public function sendContact(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'message' => 'required|string',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for reaching out to HealthPulse Hospital. Our medical coordination team will contact you shortly!'
        ]);
    }
}

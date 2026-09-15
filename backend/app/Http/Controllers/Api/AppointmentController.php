<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\OpdQueue;
use App\Models\User;
use App\Models\PatientProfile;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Events\AppointmentBookedEvent;
use App\Services\PatientVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Appointment::with(['patient.patientProfile', 'doctor.doctorProfile.department', 'department', 'prescription.items', 'opdQueue']);

        if ($user->role === 'patient') {
            $query->where('patient_id', $user->id);
        } elseif ($user->role === 'doctor') {
            $query->where('doctor_id', $user->id);
        }

        $appointments = $query->orderBy('appointment_date', 'desc')->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'appointments' => $appointments
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'nullable|string',
            'symptoms' => 'nullable|string',
            'patient_name' => 'nullable|string',
            'patient_phone' => 'nullable|string',
            'patient_email' => 'nullable|email',
            'cnic' => 'nullable|string',
        ], [
            'appointment_date.after_or_equal' => 'The appointment date cannot be before today.',
        ]);

        // 1. Strict Identity Verification: CNIC, Phone, Disposable Email
        $normalizedCnic = null;
        if ($request->cnic) {
            $cnicCheck = PatientVerificationService::validateCnic($request->cnic);
            if (!$cnicCheck['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $cnicCheck['error'],
                ], 422);
            }
            $normalizedCnic = $cnicCheck['normalized'];
        }

        $normalizedPhone = null;
        if ($request->patient_phone) {
            $phoneCheck = PatientVerificationService::validatePhone($request->patient_phone);
            if (!$phoneCheck['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $phoneCheck['error'],
                ], 422);
            }
            $normalizedPhone = $phoneCheck['normalized'];
        }

        if ($request->patient_email) {
            $emailCheck = PatientVerificationService::validateEmail($request->patient_email);
            if (!$emailCheck['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $emailCheck['error'],
                ], 422);
            }
        }

        // 2. Patient User Resolution (by CNIC first, then authenticated user, then Email)
        $currentUser = $request->user('sanctum');
        $patientId = null;
        $patientUser = null;

        if ($currentUser) {
            if ($currentUser->role === 'patient') {
                $patientUser = $currentUser;
                $patientId = $currentUser->id;
                $patientName = $request->patient_name ?: $currentUser->name;
                $patientPhone = $normalizedPhone ?: $currentUser->phone;
            } else {
                // Admin/Doctor/Receptionist booking on behalf of patient
                if ($normalizedCnic) {
                    $existingProfile = PatientProfile::where('cnic', $normalizedCnic)->first();
                    if ($existingProfile) {
                        $patientUser = $existingProfile->user;
                    }
                }

                if (!$patientUser) {
                    $email = $request->patient_email ?: 'patient_' . time() . '@healthpulse.com';
                    $patientUser = User::firstOrCreate(
                        ['email' => $email],
                        [
                            'name' => $request->patient_name ?? 'Valued Patient',
                            'phone' => $normalizedPhone ?? '0300-1234567',
                            'password' => bcrypt('password123'),
                            'role' => 'patient',
                            'avatar' => 'assets/images/default-patient.svg',
                        ]
                    );
                }

                $patientId = $patientUser->id;
                $patientName = $request->patient_name ?: $patientUser->name;
                $patientPhone = $normalizedPhone ?: $patientUser->phone;
            }
        } else {
            // Guest booking: match by CNIC first, then Email
            if ($normalizedCnic) {
                $existingProfile = PatientProfile::where('cnic', $normalizedCnic)->first();
                if ($existingProfile) {
                    $patientUser = $existingProfile->user;
                }
            }

            if (!$patientUser) {
                $email = $request->patient_email ?: 'patient_' . time() . '@healthpulse.com';
                $userMatch = User::where('email', $email)->first();
                if ($userMatch) {
                    $patientUser = $userMatch;
                } else {
                    $patientUser = User::create([
                        'name' => $request->patient_name ?? 'Valued Patient',
                        'email' => $email,
                        'phone' => $normalizedPhone ?? '0300-1234567',
                        'password' => bcrypt('password123'),
                        'role' => 'patient',
                        'avatar' => 'assets/images/default-patient.svg',
                    ]);
                }
            }

            $patientId = $patientUser->id;
            $patientName = $request->patient_name ?: $patientUser->name;
            $patientPhone = $normalizedPhone ?: $patientUser->phone;
        }

        // Ensure PatientProfile exists with MRN and CNIC
        if ($patientUser) {
            $patientProfile = PatientProfile::firstOrCreate(
                ['user_id' => $patientUser->id],
                [
                    'mrn' => 'MRN-' . date('Y') . '-' . str_pad($patientUser->id, 5, '0', STR_PAD_LEFT),
                    'cnic' => $normalizedCnic,
                    'gender' => 'male',
                ]
            );
            if ($normalizedCnic && !$patientProfile->cnic) {
                $patientProfile->cnic = $normalizedCnic;
                $patientProfile->save();
            }
        }

        $doctor = User::with('doctorProfile')->findOrFail($request->doctor_id);

        // Production Duplicate Check:
        // A patient cannot have multiple active appointments with the same doctor on the same date.
        // Active statuses: pending, confirmed, in_consultation
        if ($request->type === 'service') {
            $serviceName = $request->service_name ?: 'Diagnostic Service';
            $existingService = Appointment::with('opdQueue')
                ->whereDate('appointment_date', $request->appointment_date)
                ->whereIn('status', ['pending', 'confirmed', 'in_consultation'])
                ->where(function ($query) use ($patientId, $patientName, $patientPhone, $normalizedCnic) {
                    $query->where('patient_id', $patientId);
                    if ($normalizedCnic) {
                        $query->orWhere('cnic', $normalizedCnic)
                              ->orWhereHas('opdQueue', function ($q) use ($normalizedCnic) {
                                  $q->where('cnic', $normalizedCnic);
                              });
                    }
                    if ($patientPhone) {
                        $query->orWhereHas('opdQueue', function ($q) use ($patientPhone, $patientName) {
                            $q->where('patient_phone', $patientPhone);
                            if ($patientName) {
                                $q->orWhere('patient_name', $patientName);
                            }
                        });
                    }
                })
                ->where(function ($q) use ($serviceName, $request) {
                    $q->where('token_no', 'like', 'S-%')
                      ->orWhere('type', 'routine')
                      ->orWhere('symptoms', 'like', "%{$serviceName}%")
                      ->orWhere('patient_notes', 'like', "%{$serviceName}%")
                      ->orWhereHas('opdQueue', function ($qQueue) use ($request) {
                          $qQueue->where('token_number', 'like', 'S-%');
                          if ($request->facility_room) {
                              $qQueue->where('counter_no', $request->facility_room);
                          }
                      });
                })
                ->first();

            if ($existingService) {
                $existingToken = $existingService->token_no ?: ($existingService->opdQueue ? $existingService->opdQueue->token_number : 'Active');
                $timeSlotMsg = $existingService->time_slot ? " at {$existingService->time_slot}" : '';
                return response()->json([
                    'success' => false,
                    'message' => "An active diagnostic booking for '{$serviceName}' already exists for {$patientName} on {$request->appointment_date}{$timeSlotMsg} (Token: {$existingToken}). Duplicate bookings for the same diagnostic service on the same date are not allowed.",
                ], 422);
            }
        } else {
            $existingAppointment = Appointment::with('opdQueue')
                ->where('doctor_id', $doctor->id)
                ->whereDate('appointment_date', $request->appointment_date)
                ->where('type', '!=', 'routine')
                ->where('token_no', 'not like', 'S-%')
                ->whereIn('status', ['pending', 'confirmed', 'in_consultation'])
                ->where(function ($query) use ($patientId, $patientName, $patientPhone, $normalizedCnic) {
                    $query->where('patient_id', $patientId);
                    if ($normalizedCnic) {
                        $query->orWhere('cnic', $normalizedCnic)
                              ->orWhereHas('opdQueue', function ($q) use ($normalizedCnic) {
                                  $q->where('cnic', $normalizedCnic);
                              });
                    }
                    if ($patientPhone) {
                        $query->orWhereHas('opdQueue', function ($q) use ($patientPhone, $patientName) {
                            $q->where('patient_phone', $patientPhone);
                            if ($patientName) {
                                $q->orWhere('patient_name', $patientName);
                            }
                        });
                    }
                })
                ->first();

            if ($existingAppointment) {
                $existingToken = $existingAppointment->token_no 
                    ?: ($existingAppointment->opdQueue ? $existingAppointment->opdQueue->token_number : 'Active');
                $timeSlotMsg = $existingAppointment->time_slot ? " at {$existingAppointment->time_slot}" : '';
                $docDisplayName = preg_match('/^dr\.?\s+/i', $doctor->name) ? $doctor->name : "Dr. {$doctor->name}";
                return response()->json([
                    'success' => false,
                    'message' => "An active appointment is already booked for {$patientName} with {$docDisplayName} on {$request->appointment_date}{$timeSlotMsg} (Token: {$existingToken}). Duplicate bookings for the same doctor on the same date are not allowed.",
                ], 422);
            }
        }

        $serviceFee = $request->fee ? (float)$request->fee : ($doctor->doctorProfile ? $doctor->doctorProfile->consultation_fee : 50.00);

        // Generate guaranteed unique token and appointment number
        $today = date('Y-m-d');
        $todayCount = OpdQueue::whereDate('created_at', $today)->count();
        $nextNumber = $todayCount + 1;
        do {
            $tokenNo = ($request->type === 'service' ? 'S-' : 'T-') . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $exists = OpdQueue::whereDate('created_at', $today)->where('token_number', $tokenNo)->exists();
            if ($exists) {
                $nextNumber++;
            }
        } while ($exists);

        $appointmentNo = ($request->type === 'service' ? 'SRV-' : 'APT-') . strtoupper(Str::random(6));
        $appointmentType = in_array($request->type, ['opd', 'emergency', 'follow_up', 'routine']) ? $request->type : 'opd';

        $appointment = Appointment::create([
            'appointment_no' => $appointmentNo,
            'patient_id' => $patientId,
            'cnic' => $normalizedCnic,
            'doctor_id' => $doctor->id,
            'department_id' => $request->department_id ?? ($doctor->doctorProfile ? $doctor->doctorProfile->department_id : null),
            'appointment_date' => $request->appointment_date,
            'time_slot' => $request->time_slot ?? '10:00 AM',
            'token_no' => $tokenNo,
            'status' => 'confirmed',
            'type' => $request->type === 'service' ? 'routine' : $appointmentType,
            'symptoms' => $request->type === 'service' 
                ? ($request->service_name ?: 'Diagnostic & Clinical Lab Procedure') 
                : $request->symptoms,
            'patient_notes' => $request->type === 'service' 
                ? ("Diagnostic Service: " . ($request->service_name ?: 'Clinical Test') . ($request->facility_room ? " | Location: " . $request->facility_room : '') . ($request->patient_notes ? " | " . $request->patient_notes : ''))
                : $request->patient_notes,
            'fee' => $serviceFee,
            'payment_status' => 'unpaid',
        ]);

        // Automatically create OPD Queue Entry in pending_checkin status
        // Patient's slot & token are reserved; queue activates to 'waiting' upon Reception Desk check-in & fee collection
        $opdQueue = OpdQueue::create([
            'token_number' => $tokenNo,
            'doctor_id' => $doctor->id,
            'department_id' => $appointment->department_id,
            'patient_id' => $patientId,
            'cnic' => $normalizedCnic,
            'patient_name' => $patientName,
            'patient_phone' => $patientPhone,
            'appointment_id' => $appointment->id,
            'counter_no' => $request->type === 'service' 
                ? ($request->facility_room ?? ($doctor->doctorProfile ? $doctor->doctorProfile->room_no : 'Diagnostic Center'))
                : ($doctor->doctorProfile ? $doctor->doctorProfile->room_no : 'Room 101'),
            'status' => 'pending_checkin',
            'queue_order' => $nextNumber,
        ]);

        // Generate Invoice with Unpaid status (Pay on Arrival at Reception Desk)
        $invoice = Invoice::create([
            'invoice_no' => 'INV-' . strtoupper(Str::random(6)),
            'patient_id' => $patientId,
            'appointment_id' => $appointment->id,
            'subtotal' => $serviceFee,
            'tax' => 0.00,
            'discount' => 0.00,
            'total_amount' => $serviceFee,
            'paid_amount' => 0.00,
            'payment_status' => 'unpaid',
            'payment_method' => 'cash',
            'paid_at' => null,
        ]);

        $itemDescription = $request->type === 'service' && $request->service_name
            ? "Clinical Service / Diagnostic Procedure - {$request->service_name}"
            : "Doctor OPD Consultation - {$doctor->name}";

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => $itemDescription,
            'quantity' => 1,
            'unit_price' => $serviceFee,
            'total' => $serviceFee,
        ]);

        // Dispatch Event for real-time notification / logs
        event(new AppointmentBookedEvent($appointment));

        return response()->json([
            'success' => true,
            'message' => "Appointment booked successfully! Your Token is {$tokenNo}. Please present this token and settle consultation fee at the Central Reception Desk upon arrival.",
            'appointment' => $appointment->load(['doctor.doctorProfile', 'department', 'opdQueue']),
            'token_number' => $tokenNo,
            'invoice' => $invoice,
        ], 201);
    }

    public function checkInPatient(Request $request, $id)
    {
        $appointment = Appointment::with(['doctor.doctorProfile', 'department', 'opdQueue', 'patient.patientProfile'])
            ->findOrFail($id);

        // 1. Mark appointment as paid and confirmed
        $appointment->payment_status = 'paid';
        if ($appointment->status === 'pending') {
            $appointment->status = 'confirmed';
        }
        $appointment->save();

        // 2. Mark invoice as paid
        $invoice = Invoice::where('appointment_id', $appointment->id)->first();
        if ($invoice) {
            $invoice->payment_status = 'paid';
            $invoice->paid_amount = $invoice->total_amount;
            $invoice->paid_at = now();
            $invoice->payment_method = $request->payment_method ?? 'cash';
            $invoice->save();
        }

        // 3. Activate OPD Queue to 'waiting' so it enters the live waiting hall & doctor call queue
        if ($appointment->opdQueue) {
            $appointment->opdQueue->status = 'waiting';
            $appointment->opdQueue->save();
        } else {
            $today = date('Y-m-d');
            $todayCount = OpdQueue::whereDate('created_at', $today)->count();
            $queue = OpdQueue::create([
                'token_number' => $appointment->token_no ?: ('T-' . str_pad($todayCount + 1, 3, '0', STR_PAD_LEFT)),
                'doctor_id' => $appointment->doctor_id,
                'department_id' => $appointment->department_id,
                'patient_id' => $appointment->patient_id,
                'cnic' => $appointment->cnic,
                'patient_name' => $appointment->patient ? $appointment->patient->name : 'Patient',
                'patient_phone' => $appointment->patient ? $appointment->patient->phone : null,
                'appointment_id' => $appointment->id,
                'counter_no' => $appointment->doctor && $appointment->doctor->doctorProfile ? $appointment->doctor->doctorProfile->room_no : 'Room 101',
                'status' => 'waiting',
                'queue_order' => $todayCount + 1,
            ]);
            $appointment->setRelation('opdQueue', $queue);
        }

        return response()->json([
            'success' => true,
            'message' => "Patient checked in successfully! Consultation fee of $" . number_format($appointment->fee, 2) . " received in cash. Token {$appointment->token_no} is now active in Waiting Hall.",
            'appointment' => $appointment->fresh(['doctor.doctorProfile', 'department', 'opdQueue', 'patient.patientProfile']),
            'invoice' => $invoice,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,in_consultation,completed,cancelled'
        ]);

        $appointment = Appointment::findOrFail($id);
        $appointment->status = $request->status;
        $appointment->save();

        if ($appointment->opdQueue) {
            if ($request->status === 'in_consultation') {
                $appointment->opdQueue->status = 'calling';
                $appointment->opdQueue->called_at = now();
            } elseif ($request->status === 'completed') {
                $appointment->opdQueue->status = 'completed';
            } elseif ($request->status === 'cancelled') {
                $appointment->opdQueue->status = 'skipped';
            }
            $appointment->opdQueue->save();
        }

        return response()->json([
            'success' => true,
            'message' => "Appointment status updated to {$request->status}",
            'appointment' => $appointment
        ]);
    }
}

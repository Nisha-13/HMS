<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\QueueController;
use App\Http\Controllers\Api\WardBedController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\AdminController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    // API V1 Status & Discovery
    Route::get('/', function () {
        return response()->json([
            'status' => 'online',
            'api_version' => 'v1.0.0',
            'service' => 'HealthPulse Hospital & Research Center REST API',
            'timestamp' => now()->toIso8601String(),
            'documentation' => [
                'public_endpoints' => [
                    'GET /api/v1/departments' => 'Hospital specialist departments',
                    'GET /api/v1/doctors' => 'Doctor faculty with profiles and consultation fees',
                    'GET /api/v1/services' => 'Medical services and diagnostic catalog',
                    'GET /api/v1/events' => 'Free health camps and upcoming outreach events',
                    'GET /api/v1/stats' => 'Real-time hospital operations KPIs and statistics',
                    'GET /api/v1/queue/live' => 'Waiting hall live OPD television display feed',
                    'POST /api/v1/appointments/book' => 'Book consultation appointment (guest or user)',
                    'POST /api/v1/events/register' => 'Register for free community medical camp',
                ],
                'auth_endpoints' => [
                    'POST /api/v1/auth/login' => 'User portal login (returns Sanctum Bearer token)',
                    'POST /api/v1/auth/register' => 'Patient account registration with avatar support',
                    'GET /api/v1/auth/me' => 'Current authenticated user profile',
                    'POST /api/v1/auth/avatar' => 'Update user profile avatar',
                ],
                'admin_management_endpoints' => [
                    'GET /api/v1/admin/analytics' => 'Executive analytics and KPI metrics',
                    'GET /api/v1/admin/audit-logs' => 'Hospital security audit trail',
                    'POST /api/v1/admin/doctors' => 'Register new specialist physician',
                    'DELETE /api/v1/admin/doctors/{id}' => 'Remove doctor from clinical faculty',
                    'POST /api/v1/admin/departments' => 'Create new medical department',
                    'DELETE /api/v1/admin/departments/{id}' => 'Delete department',
                    'POST /api/v1/admin/services' => 'Add new diagnostic service / procedure',
                    'DELETE /api/v1/admin/services/{id}' => 'Remove diagnostic service',
                    'POST /api/v1/admin/events' => 'Schedule health camp / community event',
                    'DELETE /api/v1/admin/events/{id}' => 'Cancel scheduled event',
                ]
            ]
        ]);
    });

    // Auth
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login'])->name('login');

    // Public Hospital Info
    Route::get('/departments', [PublicController::class, 'getDepartments']);
    Route::get('/doctors', [PublicController::class, 'getDoctors']);
    Route::get('/services', [PublicController::class, 'getServices']);
    Route::get('/events', [PublicController::class, 'getEvents']);
    Route::get('/testimonials', [PublicController::class, 'getTestimonials']);
    Route::get('/stats', [PublicController::class, 'getHospitalStats']);
    Route::post('/contact', [PublicController::class, 'sendContact']);

    // Public Live Queue Screen (For Waiting Hall TVs)
    Route::get('/queue/live', [QueueController::class, 'getLiveQueue']);

    // Public Events Registration
    Route::post('/events/register', [PublicController::class, 'registerEvent']);
    Route::get('/events/registrations', [PublicController::class, 'getEventRegistrations']);
    Route::patch('/events/registrations/{id}/status', [PublicController::class, 'updateEventRegistrationStatus']);

    // Public / Guest Appointment Booking
    Route::post('/appointments/book', [AppointmentController::class, 'store']);

    /*
    |--------------------------------------------------------------------------
    | Protected Routes (Sanctum)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {
        // User Profile & Avatar & Logout
        Route::get('/auth/me', [AuthController::class, 'profile']);
        Route::post('/auth/avatar', [AuthController::class, 'updateAvatar']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Appointments & Camp Passes
        Route::get('/appointments', [AppointmentController::class, 'index']);
        Route::put('/appointments/{id}/status', [AppointmentController::class, 'updateStatus']);
        Route::post('/appointments/{id}/check-in', [AppointmentController::class, 'checkInPatient']);
        Route::get('/patient/event-registrations', [PublicController::class, 'getPatientEventRegistrations']);

        // Prescriptions & EMR
        Route::get('/prescriptions', [PrescriptionController::class, 'index']);
        Route::post('/prescriptions', [PrescriptionController::class, 'store']);

        // Doctor Specific
        Route::get('/doctor/dashboard', [DoctorController::class, 'getDoctorDashboard']);
        Route::post('/doctor/call-patient', [DoctorController::class, 'callPatient']);

        // OPD Queue Management
        Route::post('/queue/generate-token', [QueueController::class, 'generateWalkInToken']);
        Route::post('/queue/walk-in', [QueueController::class, 'generateWalkInToken']);

        // Inpatient Ward & Beds
        Route::get('/wards', [WardBedController::class, 'index']);
        Route::post('/wards/admit', [WardBedController::class, 'admitPatient']);
        Route::post('/beds/{id}/discharge', [WardBedController::class, 'dischargePatient']);

        // Billing & Invoices
        Route::get('/invoices', [BillingController::class, 'index']);
        Route::post('/invoices/{id}/pay', [BillingController::class, 'markAsPaid']);

        // Admin Analytics & Logs & Management
        Route::get('/admin/analytics', [AdminController::class, 'getAnalytics']);
        Route::get('/admin/audit-logs', [AdminController::class, 'getAuditLogs']);
        Route::post('/admin/doctors', [AdminController::class, 'createDoctor']);
        Route::delete('/admin/doctors/{id}', [AdminController::class, 'deleteDoctor']);
        Route::post('/admin/departments', [AdminController::class, 'createDepartment']);
        Route::delete('/admin/departments/{id}', [AdminController::class, 'deleteDepartment']);
        Route::post('/admin/services', [AdminController::class, 'createService']);
        Route::delete('/admin/services/{id}', [AdminController::class, 'deleteService']);
        Route::post('/admin/events', [AdminController::class, 'createEvent']);
        Route::delete('/admin/events/{id}', [AdminController::class, 'deleteEvent']);
    });
});

/*
|--------------------------------------------------------------------------
| Temporary Deployment Database Trigger Route
|--------------------------------------------------------------------------
*/
Route::get('/run-migrations', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        return response()->json([
            'success' => true,
            'message' => 'Mubarak ho! Database Migrated and Seeded Successfully.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

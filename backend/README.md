# HealthPulse HMS — Backend (Laravel 11 API)

**Stack:** PHP 8.2 · Laravel 11 · MySQL · Eloquent ORM · Laravel Sanctum · Events · Listeners · Queue Jobs

## Project Structure

```
backend/
├── app/
│   ├── Events/
│   │   ├── AppointmentBookedEvent.php
│   │   ├── QueueTokenCalledEvent.php
│   │   └── EmergencyAlertEvent.php
│   ├── Jobs/
│   │   └── ProcessBillingInvoiceJob.php
│   ├── Listeners/
│   │   └── NotifyAppointmentPartiesListener.php
│   ├── Models/
│   │   ├── User.php              ← Roles: admin, doctor, patient, receptionist
│   │   ├── Department.php
│   │   ├── DoctorProfile.php
│   │   ├── PatientProfile.php
│   │   ├── Appointment.php
│   │   ├── Prescription.php
│   │   ├── PrescriptionItem.php
│   │   ├── OpdQueue.php
│   │   ├── Ward.php
│   │   ├── Bed.php
│   │   ├── Invoice.php
│   │   ├── InvoiceItem.php
│   │   ├── Service.php
│   │   ├── HospitalEvent.php
│   │   ├── Testimonial.php
│   │   └── AuditLog.php
│   └── Http/Controllers/Api/
│       ├── AuthController.php        ← Sanctum register / login / logout
│       ├── PublicController.php      ← Departments, Doctors, Services (no auth)
│       ├── AppointmentController.php ← Book, token, dispatch events
│       ├── DoctorController.php      ← OPD queue, call next patient
│       ├── PrescriptionController.php← Digital EMR prescriptions
│       ├── QueueController.php       ← Live queue stream, walk-in tokens
│       ├── WardBedController.php     ← Ward occupancy, admit/discharge
│       ├── BillingController.php     ← Invoices, payment settlement
│       └── AdminController.php       ← Analytics KPIs, audit logs
├── database/
│   ├── migrations/                   ← 13 migration files
│   └── seeders/
│       └── DatabaseSeeder.php        ← Demo users, departments, doctors, queue data
├── routes/
│   └── api.php                       ← /api/v1 public + Sanctum protected routes
└── .env                              ← MySQL: healthpulse_hms_db
```

## Dev Server

```bash
php artisan serve --port=8000   # http://127.0.0.1:8000
```

## Database

```bash
php artisan migrate:fresh --seed  # Reset + seed all demo data
```

## Demo Users (password: `password`)

| Role | Email |
| ---- | ----- |
| Admin | admin@healthpulse.com |
| Doctor (Cardiology) | dr.john@healthpulse.com |
| Doctor (Neurology) | dr.nancy@healthpulse.com |
| Patient | patient@healthpulse.com |
| Receptionist | reception@healthpulse.com |

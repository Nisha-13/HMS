<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\DoctorProfile;
use App\Models\PatientProfile;
use App\Models\Appointment;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\OpdQueue;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use App\Models\HospitalEvent;
use App\Models\Testimonial;
use App\Models\AuditLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin User
        $admin = User::create([
            'name' => 'Dr. Robert Harrison (Medical Director)',
            'email' => 'admin@healthpulse.com',
            'phone' => '+1 (800) 456-7890',
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('password'),
            'avatar' => 'assets/images/doc4.jpg',
        ]);

        // 2. Receptionist / OPD Staff
        $receptionist = User::create([
            'name' => 'Sarah Jenkins (OPD Coordinator)',
            'email' => 'reception@healthpulse.com',
            'phone' => '+1 (800) 456-7891',
            'role' => 'receptionist',
            'status' => 'active',
            'password' => Hash::make('password'),
            'avatar' => 'assets/images/doc5.jpg',
        ]);

        // 3. Departments
        $departmentsData = [
            [
                'name' => 'Cardiology',
                'slug' => 'cardiology',
                'icon' => 'HeartPulse',
                'description' => 'Comprehensive cardiac care, catheterization lab, and non-invasive coronary diagnostics.',
                'image' => 'assets/images/dep1.jpg',
                'head_doctor_name' => 'Dr. John Allen, MD, FACC',
                'room_floor' => '2nd Floor, Wing A',
            ],
            [
                'name' => 'Neurologist',
                'slug' => 'neurologist',
                'icon' => 'Brain',
                'description' => 'Advanced neuro-critical care, stroke treatment unit, and brain health management.',
                'image' => 'assets/images/dep2.jpg',
                'head_doctor_name' => 'Dr. Nancy Lewis, MD',
                'room_floor' => '3rd Floor, Neuro Center',
            ],
            [
                'name' => 'Pediatric',
                'slug' => 'pediatric',
                'icon' => 'Baby',
                'description' => 'Dedicated child healthcare, pediatric emergency triage, and neonatal ICU (NICU).',
                'image' => 'assets/images/dep3.jpg',
                'head_doctor_name' => 'Dr. Smith Ethan, MD, FAAP',
                'room_floor' => '1st Floor, Children Wing',
            ],
            [
                'name' => 'Traumatology & Orthopedics',
                'slug' => 'traumatology',
                'icon' => 'Activity',
                'description' => '24/7 level-1 emergency trauma care, joint replacement, and orthopedic rehabilitation.',
                'image' => 'assets/images/dep4.jpg',
                'head_doctor_name' => 'Dr. Kirk Romanov, MS, FRCS',
                'room_floor' => 'Ground Floor, Trauma Wing',
            ],
            [
                'name' => 'Dental Care & Surgery',
                'slug' => 'dental',
                'icon' => 'Smile',
                'description' => 'Cosmetic dentistry, orthodontics, oral implants, and maxillofacial surgery.',
                'image' => 'assets/images/dep5.jpg',
                'head_doctor_name' => 'Dr. Adaline Becka, DDS',
                'room_floor' => '2nd Floor, Clinic 204',
            ],
            [
                'name' => 'Urology & Nephrology',
                'slug' => 'urology',
                'icon' => 'Filter',
                'description' => 'Kidney care, advanced dialysis center, and robotic urological interventions.',
                'image' => 'assets/images/dep6.jpg',
                'head_doctor_name' => 'Dr. Howard Stern, MD',
                'room_floor' => '4th Floor, Suite 402',
            ],
            [
                'name' => 'X-Ray & Radiology',
                'slug' => 'xray',
                'icon' => 'ScanLine',
                'description' => 'Digital 3D X-ray, 128-slice CT scan, 3-Tesla MRI, and ultrasound imaging.',
                'image' => 'assets/images/dep7.jpg',
                'head_doctor_name' => 'Dr. Janne Wilson, MD',
                'room_floor' => 'Basement Level 1',
            ],
            [
                'name' => 'Pulmonology & Chest',
                'slug' => 'pulmonary',
                'icon' => 'Wind',
                'description' => 'Respiratory therapy, asthma management, sleep laboratory, and bronchoscopy.',
                'image' => 'assets/images/dep8.jpg',
                'head_doctor_name' => 'Dr. Aceline Thomas, MD',
                'room_floor' => '3rd Floor, Wing B',
            ],
        ];

        $deptModels = [];
        foreach ($departmentsData as $dep) {
            $deptModels[$dep['slug']] = Department::create($dep);
        }

        // 4. Doctors
        $doctorsData = [
            [
                'name' => 'Dr. John Allen',
                'email' => 'dr.john@healthpulse.com',
                'phone' => '+1 (555) 234-5678',
                'avatar' => 'assets/images/doc1.jpg',
                'department_slug' => 'cardiology',
                'specialty' => 'Chief Interventional Cardiologist',
                'qualification' => 'MBBS, MD (Cardiology), FACC (USA)',
                'experience_years' => 14,
                'consultation_fee' => 75.00,
                'room_no' => 'Room 201 (Cardiology OPD)',
                'bio' => 'Senior Interventional Cardiologist with over 14 years of clinical excellence in coronary angioplasty and heart disease management.',
            ],
            [
                'name' => 'Dr. Nancy Lewis',
                'email' => 'dr.nancy@healthpulse.com',
                'phone' => '+1 (555) 345-6789',
                'avatar' => 'assets/images/doc2.jpg',
                'department_slug' => 'neurologist',
                'specialty' => 'Senior Neurosurgeon & Neurologist',
                'qualification' => 'MBBS, MS, MCh (Neurosurgery)',
                'experience_years' => 12,
                'consultation_fee' => 80.00,
                'room_no' => 'Room 304 (Neuro Suite)',
                'bio' => 'Specialized in stroke rehabilitation, epilepsy management, and minimally invasive cranial surgeries.',
            ],
            [
                'name' => 'Dr. Smith Ethan',
                'email' => 'dr.smith@healthpulse.com',
                'phone' => '+1 (555) 456-7890',
                'avatar' => 'assets/images/doc3.jpg',
                'department_slug' => 'pediatric',
                'specialty' => 'Consultant Pediatrician & Neonatologist',
                'qualification' => 'MBBS, DCH, MD (Pediatrics)',
                'experience_years' => 9,
                'consultation_fee' => 60.00,
                'room_no' => 'Room 105 (Children Clinic)',
                'bio' => 'Dedicated to pediatric care, immunization schedules, and infant critical care diagnostics.',
            ],
            [
                'name' => 'Dr. Kirk Romanov',
                'email' => 'dr.kirk@healthpulse.com',
                'phone' => '+1 (555) 567-8901',
                'avatar' => 'assets/images/doc4.jpg',
                'department_slug' => 'traumatology',
                'specialty' => 'Trauma Surgeon & Orthopedic Specialist',
                'qualification' => 'MBBS, MS (Orthopedics), FRCS',
                'experience_years' => 16,
                'consultation_fee' => 85.00,
                'room_no' => 'Room 102 (Trauma Center)',
                'bio' => 'Expert in complex fracture reconstruction, sports injuries, and total knee/hip joint replacements.',
            ],
            [
                'name' => 'Dr. Adaline Becka',
                'email' => 'dr.adaline@healthpulse.com',
                'phone' => '+1 (555) 678-9012',
                'avatar' => 'assets/images/doc5.jpg',
                'department_slug' => 'dental',
                'specialty' => 'Chief Dental Surgeon & Orthodontist',
                'qualification' => 'BDS, MDS (Orthodontics)',
                'experience_years' => 8,
                'consultation_fee' => 50.00,
                'room_no' => 'Dental Suite 204',
                'bio' => 'Provides pain-free dental treatments, smile design, restorative implants, and orthodontic alignment.',
            ],
            [
                'name' => 'Dr. Janne Wilson',
                'email' => 'dr.janne@healthpulse.com',
                'phone' => '+1 (555) 789-0123',
                'avatar' => 'assets/images/doc6.jpg',
                'department_slug' => 'xray',
                'specialty' => 'Senior Consultant Radiologist',
                'qualification' => 'MBBS, MD (Radiodiagnosis)',
                'experience_years' => 11,
                'consultation_fee' => 65.00,
                'room_no' => 'Imaging Suite B1',
                'bio' => 'Expertise in high-resolution MRI, CT angiography, and interventional ultrasound diagnostics.',
            ],
            [
                'name' => 'Dr. Aceline Thomas',
                'email' => 'dr.aceline@healthpulse.com',
                'phone' => '+1 (555) 890-1234',
                'avatar' => 'assets/images/doc7.jpg',
                'department_slug' => 'pulmonary',
                'specialty' => 'Pulmonologist & Sleep Specialist',
                'qualification' => 'MBBS, MD (Pulmonary Medicine)',
                'experience_years' => 10,
                'consultation_fee' => 70.00,
                'room_no' => 'Room 302 (Chest Clinic)',
                'bio' => 'Specialized in chronic lung conditions, asthma, COPD, and advanced respiratory therapy.',
            ],
        ];

        $doctorUsers = [];
        foreach ($doctorsData as $d) {
            $docUser = User::create([
                'name' => $d['name'],
                'email' => $d['email'],
                'phone' => $d['phone'],
                'role' => 'doctor',
                'status' => 'active',
                'password' => Hash::make('password'),
                'avatar' => $d['avatar'],
            ]);

            DoctorProfile::create([
                'user_id' => $docUser->id,
                'department_id' => $deptModels[$d['department_slug']]->id,
                'specialty' => $d['specialty'],
                'qualification' => $d['qualification'],
                'experience_years' => $d['experience_years'],
                'consultation_fee' => $d['consultation_fee'],
                'room_no' => $d['room_no'],
                'bio' => $d['bio'],
                'available_days' => 'Mon - Sat',
                'available_time_start' => '09:00:00',
                'available_time_end' => '17:00:00',
                'is_available' => true,
            ]);

            $doctorUsers[$d['email']] = $docUser;
        }

        // 5. Patients
        $patientsData = [
            [
                'name' => 'Emma Watson',
                'email' => 'patient@healthpulse.com',
                'phone' => '+1 (555) 912-3456',
                'blood_group' => 'O+',
                'gender' => 'female',
                'dob' => '1992-04-15',
                'allergies' => 'Penicillin, Dust',
                'medical_history' => 'Mild hypertension, routine health checks',
            ],
            [
                'name' => 'David Beckham',
                'email' => 'david.b@example.com',
                'phone' => '+1 (555) 823-4567',
                'blood_group' => 'A+',
                'gender' => 'male',
                'dob' => '1985-05-02',
                'allergies' => 'None',
                'medical_history' => 'Post-arthroscopic knee recovery',
            ],
            [
                'name' => 'Sophia Martinez',
                'email' => 'sophia.m@example.com',
                'phone' => '+1 (555) 734-5678',
                'blood_group' => 'B+',
                'gender' => 'female',
                'dob' => '1998-11-20',
                'allergies' => 'Sulfa drugs',
                'medical_history' => 'Migraine episodes',
            ],
            [
                'name' => 'Michael Chen',
                'email' => 'michael.c@example.com',
                'phone' => '+1 (555) 645-6789',
                'blood_group' => 'AB+',
                'gender' => 'male',
                'dob' => '1976-08-10',
                'allergies' => 'None',
                'medical_history' => 'Seasonal allergies',
            ]
        ];

        $patientUsers = [];
        $i = 1;
        foreach ($patientsData as $p) {
            $patUser = User::create([
                'name' => $p['name'],
                'email' => $p['email'],
                'phone' => $p['phone'],
                'role' => 'patient',
                'status' => 'active',
                'password' => Hash::make('password'),
                'avatar' => 'assets/images/testimonail' . ($i > 1 ? ($i - 1) : '') . '.jpg',
            ]);

            PatientProfile::create([
                'user_id' => $patUser->id,
                'mrn' => 'HP-' . date('Y') . '-' . str_pad($patUser->id, 4, '0', STR_PAD_LEFT),
                'blood_group' => $p['blood_group'],
                'gender' => $p['gender'],
                'dob' => $p['dob'],
                'address' => '450 Wellness Blvd, Medical District, NY',
                'emergency_contact_name' => 'Family Guardian',
                'emergency_contact_phone' => '+1 (555) 000-1122',
                'allergies' => $p['allergies'],
                'medical_history_notes' => $p['medical_history'],
            ]);

            $patientUsers[] = $patUser;
            $i++;
        }

        // 6. Appointments & OPD Queues
        $today = date('Y-m-d');
        $primaryDoc = $doctorUsers['dr.john@healthpulse.com'];
        $primaryPatient = $patientUsers[0]; // Emma Watson

        // Appointment 1 - Confirmed for Today
        $apt1 = Appointment::create([
            'appointment_no' => 'APT-78901',
            'patient_id' => $primaryPatient->id,
            'doctor_id' => $primaryDoc->id,
            'department_id' => $deptModels['cardiology']->id,
            'appointment_date' => $today,
            'time_slot' => '10:30 AM',
            'token_no' => 'T-001',
            'status' => 'confirmed',
            'type' => 'opd',
            'symptoms' => 'Occasional chest tightness and palpitations during cardio exercise',
            'patient_notes' => 'Follow up on previous ECG report',
            'fee' => 75.00,
            'payment_status' => 'paid',
        ]);

        OpdQueue::create([
            'token_number' => 'T-001',
            'doctor_id' => $primaryDoc->id,
            'department_id' => $deptModels['cardiology']->id,
            'patient_id' => $primaryPatient->id,
            'patient_name' => $primaryPatient->name,
            'patient_phone' => $primaryPatient->phone,
            'appointment_id' => $apt1->id,
            'counter_no' => 'Room 201 (Cardiology OPD)',
            'status' => 'waiting',
            'queue_order' => 1,
            'called_at' => null,
        ]);

        // Appointment 2 - Completed with Prescription
        $apt2 = Appointment::create([
            'appointment_no' => 'APT-78902',
            'patient_id' => $patientUsers[1]->id,
            'doctor_id' => $primaryDoc->id,
            'department_id' => $deptModels['cardiology']->id,
            'appointment_date' => date('Y-m-d', strtotime('-2 days')),
            'time_slot' => '11:00 AM',
            'token_no' => 'T-002',
            'status' => 'completed',
            'type' => 'routine',
            'symptoms' => 'Elevated blood pressure and fatigue',
            'fee' => 75.00,
            'payment_status' => 'paid',
        ]);

        $rx = Prescription::create([
            'prescription_no' => 'RX-88410',
            'appointment_id' => $apt2->id,
            'doctor_id' => $primaryDoc->id,
            'patient_id' => $patientUsers[1]->id,
            'diagnosis' => 'Stage 1 Essential Hypertension with mild tachycardia',
            'symptoms_summary' => 'Blood pressure 145/92 mmHg, resting pulse 88 bpm',
            'bp' => '145/92',
            'pulse' => '88 bpm',
            'temperature' => '98.4 F',
            'weight' => '78 kg',
            'advice_notes' => 'Maintain low sodium diet, avoid caffeine after 4pm, 30 minutes daily brisk walk.',
            'follow_up_date' => date('Y-m-d', strtotime('+14 days')),
        ]);

        PrescriptionItem::create([
            'prescription_id' => $rx->id,
            'medicine_name' => 'Amlodipine 5mg',
            'dosage' => '1 tablet',
            'frequency' => '1-0-0 (Morning)',
            'duration' => '30 days',
            'instruction' => 'Take with water before breakfast',
        ]);

        PrescriptionItem::create([
            'prescription_id' => $rx->id,
            'medicine_name' => 'Metoprolol Tartrate 25mg',
            'dosage' => '1 tablet',
            'frequency' => '0-0-1 (Night)',
            'duration' => '15 days',
            'instruction' => 'Take after dinner',
        ]);

        // More Queue Tokens for Today
        $neuroDoc = $doctorUsers['dr.nancy@healthpulse.com'];
        OpdQueue::create([
            'token_number' => 'T-002',
            'doctor_id' => $primaryDoc->id,
            'department_id' => $deptModels['cardiology']->id,
            'patient_id' => $patientUsers[2]->id,
            'patient_name' => $patientUsers[2]->name,
            'patient_phone' => $patientUsers[2]->phone,
            'counter_no' => 'Room 201 (Cardiology OPD)',
            'status' => 'waiting',
            'queue_order' => 2,
        ]);

        OpdQueue::create([
            'token_number' => 'W-003',
            'doctor_id' => $primaryDoc->id,
            'department_id' => $deptModels['cardiology']->id,
            'patient_id' => null,
            'patient_name' => 'Arthur Morgan (Walk-in)',
            'patient_phone' => '+1 (555) 123-9999',
            'counter_no' => 'Room 201 (Cardiology OPD)',
            'status' => 'waiting',
            'queue_order' => 3,
        ]);

        OpdQueue::create([
            'token_number' => 'N-101',
            'doctor_id' => $neuroDoc->id,
            'department_id' => $deptModels['neurologist']->id,
            'patient_id' => $patientUsers[3]->id,
            'patient_name' => $patientUsers[3]->name,
            'patient_phone' => $patientUsers[3]->phone,
            'counter_no' => 'Room 304 (Neuro Suite)',
            'status' => 'waiting',
            'queue_order' => 1,
        ]);

        // 7. Wards & Beds
        $wardsData = [
            ['name' => 'Intensive Care Unit (ICU)', 'floor' => '2nd Floor, Wing C', 'type' => 'icu', 'total_beds' => 8, 'daily_rate' => 350.00],
            ['name' => 'Coronary Care Unit (CCU)', 'floor' => '2nd Floor, Wing A', 'type' => 'ccu', 'total_beds' => 6, 'daily_rate' => 300.00],
            ['name' => 'Executive VIP Suite', 'floor' => '4th Floor, Luxury Wing', 'type' => 'vip_suite', 'total_beds' => 5, 'daily_rate' => 250.00],
            ['name' => 'General Medical Ward (Male)', 'floor' => '1st Floor, East Wing', 'type' => 'general', 'total_beds' => 12, 'daily_rate' => 80.00],
            ['name' => 'General Medical Ward (Female)', 'floor' => '1st Floor, West Wing', 'type' => 'general', 'total_beds' => 12, 'daily_rate' => 80.00],
            ['name' => 'Pediatric Care Ward', 'floor' => '3rd Floor, Children Care', 'type' => 'pediatric', 'total_beds' => 10, 'daily_rate' => 110.00],
        ];

        foreach ($wardsData as $wData) {
            $w = Ward::create($wData);
            for ($b = 1; $b <= $w->total_beds; $b++) {
                $isOcc = ($b == 1 && $w->type == 'icu') || ($b == 2 && $w->type == 'general');
                Bed::create([
                    'ward_id' => $w->id,
                    'bed_number' => strtoupper(substr($w->type, 0, 3)) . '-' . str_pad($b, 2, '0', STR_PAD_LEFT),
                    'is_occupied' => $isOcc,
                    'patient_id' => $isOcc ? $patientUsers[1]->id : null,
                    'patient_name' => $isOcc ? 'David Beckham' : null,
                    'admitted_at' => $isOcc ? now()->subDays(1) : null,
                ]);
            }
        }

        // 8. Invoices
        $inv1 = Invoice::create([
            'invoice_no' => 'INV-9021',
            'patient_id' => $primaryPatient->id,
            'appointment_id' => $apt1->id,
            'subtotal' => 75.00,
            'tax' => 0.00,
            'discount' => 0.00,
            'total_amount' => 75.00,
            'paid_amount' => 75.00,
            'payment_status' => 'paid',
            'payment_method' => 'online',
            'paid_at' => now(),
        ]);
        InvoiceItem::create([
            'invoice_id' => $inv1->id,
            'description' => 'Cardiology Specialist Consultation - Dr. John Allen',
            'quantity' => 1,
            'unit_price' => 75.00,
            'total' => 75.00,
        ]);

        $inv2 = Invoice::create([
            'invoice_no' => 'INV-9022',
            'patient_id' => $patientUsers[1]->id,
            'appointment_id' => $apt2->id,
            'subtotal' => 225.00,
            'tax' => 15.00,
            'discount' => 20.00,
            'total_amount' => 220.00,
            'paid_amount' => 220.00,
            'payment_status' => 'paid',
            'payment_method' => 'credit_card',
            'paid_at' => now()->subDays(1),
        ]);
        InvoiceItem::create([
            'invoice_id' => $inv2->id,
            'description' => 'Cardiology Consultation & Diagnostic ECG',
            'quantity' => 1,
            'unit_price' => 125.00,
            'total' => 125.00,
        ]);
        InvoiceItem::create([
            'invoice_id' => $inv2->id,
            'description' => 'Automated Lipid Profile & Blood Panel',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total' => 100.00,
        ]);

        // 9. Services
        $servicesData = [
            [
                'title' => '24/7 Level-1 Emergency & Trauma Care',
                'slug' => 'emergency-trauma',
                'description' => 'Immediate round-the-clock emergency medical response, rapid resuscitation bays, and dedicated trauma surgical suites.',
                'image' => 'assets/images/service1.jpg',
                'icon' => 'Ambulance',
                'starting_price' => 0.00,
                'department_name' => 'Emergency & Trauma Center',
            ],
            [
                'title' => 'Advanced Robotic & Minimally Invasive Surgery',
                'slug' => 'robotic-surgery',
                'description' => 'Next-generation da Vinci robotic systems for ultra-precise laparoscopic, gynecologic, and urologic procedures with faster recovery.',
                'image' => 'assets/images/service2.jpg',
                'icon' => 'Scissors',
                'starting_price' => 450.00,
                'department_name' => 'Surgical Institute',
            ],
            [
                'title' => 'Comprehensive Cardiac Catheterization Lab',
                'slug' => 'cardiac-cath-lab',
                'description' => 'State-of-the-art biplane cath lab for primary angioplasty, pacemaker implantations, and complex coronary stenting.',
                'image' => 'assets/images/service3.jpg',
                'icon' => 'HeartHandshake',
                'starting_price' => 300.00,
                'department_name' => 'Cardiology Center',
            ],
            [
                'title' => '3-Tesla MRI & 128-Slice CT Diagnostics',
                'slug' => 'radiology-imaging',
                'description' => 'Ultra-quiet 3T magnetic resonance imaging, ultra-low radiation CT scans, and high-frequency digital ultrasound.',
                'image' => 'assets/images/service4.png',
                'icon' => 'Scan',
                'starting_price' => 80.00,
                'department_name' => 'Radiology & Imaging',
            ],
            [
                'title' => 'Pediatric ICU & Child Health Wellness',
                'slug' => 'pediatric-icu',
                'description' => 'Specialized neonatal and pediatric intensive care with specialized pediatric nurses and child life specialists.',
                'image' => 'assets/images/service5.jpg',
                'icon' => 'Baby',
                'starting_price' => 120.00,
                'department_name' => 'Pediatrics Institute',
            ],
            [
                'title' => 'Modern Dialysis & Kidney Transplant Unit',
                'slug' => 'dialysis-nephrology',
                'description' => 'Hemodialysis suites with hospital-grade ultra-pure water filtration systems and 24/7 nephrology supervision.',
                'image' => 'assets/images/service6.jpg',
                'icon' => 'Droplet',
                'starting_price' => 95.00,
                'department_name' => 'Nephrology & Urology',
            ],
        ];

        foreach ($servicesData as $s) {
            Service::create($s);
        }

        // 10. Hospital Events & Free Health Camps
        $eventsData = [
            [
                'title' => 'Free Community Heart Screening & ECG Camp',
                'slug' => 'free-heart-screening-camp',
                'category' => 'Community Health Drive',
                'event_date' => date('Y-m-d', strtotime('+7 days')),
                'time_slot' => '09:00 AM - 04:00 PM',
                'location' => 'HealthPulse Main OPD Auditorium',
                'description' => 'Complimentary blood pressure checkup, 12-lead ECG, blood sugar test, and direct consultation with senior cardiologists.',
                'image' => 'assets/images/Event1.jpg',
                'organizer' => 'HealthPulse Cardiology Department',
            ],
            [
                'title' => 'Annual International Medical Symposium 2026',
                'slug' => 'annual-medical-symposium-2026',
                'category' => 'Medical Conference',
                'event_date' => date('Y-m-d', strtotime('+18 days')),
                'time_slot' => '10:00 AM - 06:00 PM',
                'location' => 'Convention Hall, Floor 5',
                'description' => 'Keynote addresses on AI-assisted surgical diagnostics, robotics in neuro-intervention, and modern clinical paradigms.',
                'image' => 'assets/images/Event2.jpg',
                'organizer' => 'HealthPulse Academic & Research Board',
            ],
            [
                'title' => 'Blood Donation & Platelet Drive',
                'slug' => 'blood-donation-drive',
                'category' => 'Charity & Donation',
                'event_date' => date('Y-m-d', strtotime('+25 days')),
                'time_slot' => '08:30 AM - 05:00 PM',
                'location' => 'Blood Bank & Donor Center',
                'description' => 'Join our life-saving community mission. Every single blood donor receives a complete free health biomarker screening kit.',
                'image' => 'assets/images/Event3.jpg',
                'organizer' => 'HealthPulse Transfusion Services',
            ],
            [
                'title' => 'Child Nutrition & Pediatric Wellness Workshop',
                'slug' => 'child-nutrition-workshop',
                'category' => 'Parenting Workshop',
                'event_date' => date('Y-m-d', strtotime('+32 days')),
                'time_slot' => '11:00 AM - 02:00 PM',
                'location' => 'Pediatrics Seminar Room',
                'description' => 'Interactive session for parents on early childhood dietary needs, allergy prevention, and developmental milestones.',
                'image' => 'assets/images/Event4.jpg',
                'organizer' => 'HealthPulse Child Care Institute',
            ],
        ];

        foreach ($eventsData as $ev) {
            HospitalEvent::create($ev);
        }

        // 11. Testimonials
        $testimonialsData = [
            [
                'patient_name' => 'Sarah Connor',
                'patient_role' => 'Cardiology Patient',
                'department_name' => 'Cardiology',
                'comment' => 'The quick response from the cardiac trauma team at HealthPulse saved my life. Dr. John Allen and the nurses treated me with immense empathy and high-tech care.',
                'rating' => 5,
                'avatar' => 'assets/images/testimonail.jpg',
            ],
            [
                'patient_name' => 'Jonathan Myers',
                'patient_role' => 'Orthopedic Recovery',
                'department_name' => 'Traumatology & Orthopedics',
                'comment' => 'Had total knee replacement surgery here. I was walking without support in less than 3 weeks! The digital OPD queue system made every visit effortless.',
                'rating' => 5,
                'avatar' => 'assets/images/testimonail1.jpg',
            ],
            [
                'patient_name' => 'Elena Rostova',
                'patient_role' => 'Pediatric Care Parent',
                'department_name' => 'Pediatrics',
                'comment' => 'The pediatric wing is warm and welcoming for children. Dr. Smith took time to listen carefully and explained the treatment with so much patience.',
                'rating' => 5,
                'avatar' => 'assets/images/testimonail2.jpg',
            ],
        ];

        foreach ($testimonialsData as $t) {
            Testimonial::create($t);
        }

        // 12. Audit Log
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'SYSTEM_INITIALIZATION',
            'entity_type' => 'System',
            'entity_id' => 1,
            'payload' => [
                'hospital_name' => 'HealthPulse Hospital',
                'version' => '2.0-Production',
                'status' => 'All modules online',
            ],
            'ip_address' => '127.0.0.1',
        ]);
    }
}

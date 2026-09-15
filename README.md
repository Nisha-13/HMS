# HealthPulse Hospital & Research Center — HMS

A full-stack, production-grade **Hospital Management System** built with Laravel 11 + Vue 3.

## 🏗️ Architecture

```
HMS/
├── backend/                 ← Laravel 11 REST API (PHP 8.2 + MySQL)
│   ├── app/
│   │   ├── Events/          ← AppointmentBooked, QueueTokenCalled, EmergencyAlert
│   │   ├── Jobs/            ← ProcessBillingInvoice (queue worker)
│   │   ├── Listeners/       ← NotifyAppointmentParties
│   │   ├── Models/          ← 16 Eloquent ORM models
│   │   └── Http/Controllers/Api/  ← 9 RESTful API controllers
│   ├── database/
│   │   ├── migrations/      ← 13 table schemas
│   │   └── seeders/         ← Full demo data
│   ├── public/
│   │   └── assets/images/   ← All hospital images (served by Laravel)
│   └── routes/api.php       ← /api/v1 endpoints
│
└── frontend/                ← Vue 3 SPA (Vite + Tailwind CSS + Pinia)
    ├── src/
    │   ├── components/      ← 12 reusable UI sections
    │   ├── views/           ← 13 page views (public + 4 role dashboards)
    │   ├── stores/          ← 3 Pinia stores (auth, hospital, queue)
    │   ├── services/api.js  ← Axios → http://127.0.0.1:8000/api/v1
    │   └── router/index.js  ← Vue Router 4 + auth guards
    └── public/assets/images ← Logo + hero banner (frontend static)
```

## 🚀 Running Locally

```bash
# Terminal 1 — Backend API
cd backend
php artisan serve --port=8000

# Terminal 2 — Frontend SPA
cd frontend
npm run dev
```

| Portal | URL |
| ------ | --- |
| 🏠 Home | http://localhost:5173/ |
| 📺 Live OPD Queue TV | http://localhost:5173/queue-display |
| 🔑 Login | http://localhost:5173/login |
| 👑 Admin Dashboard | http://localhost:5173/admin |
| 🩺 Doctor Dashboard | http://localhost:5173/doctor |
| 🧑 Patient Portal | http://localhost:5173/patient |
| 🏥 Receptionist Console | http://localhost:5173/reception |

## 🔑 Demo Credentials (password: `password`)

| Role | Email |
| ---- | ----- |
| Admin | admin@healthpulse.com |
| Doctor (Cardiology) | dr.john@healthpulse.com |
| Doctor (Neurology) | dr.nancy@healthpulse.com |
| Patient | patient@healthpulse.com |
| Receptionist | reception@healthpulse.com |

## 🛠️ Tech Stack

| Layer | Technology |
| ----- | ---------- |
| Frontend | Vue 3 (Composition API) · Vite · Tailwind CSS · Pinia · Vue Router 4 · Axios |
| Backend | Laravel 11 · PHP 8.2 · Eloquent ORM · Laravel Sanctum |
| Database | MySQL 8+ |
| Real-time | Laravel Events · Queues · Web Speech API |

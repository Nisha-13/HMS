# HealthPulse Hospital & Research Center — HMS

A full-stack **Hospital Management System (HMS)** built with **Laravel 12, Vue 3, MySQL, Docker, and Nginx**.

The system provides separate portals and workflows for administrators, doctors, receptionists, and patients, with hospital departments, doctors, appointments, OPD queue management, services, events, notifications, billing-related workflows, and role-based access control.

---

## 🏗️ Architecture

```text
HMS/
│
├── backend/                         ← Laravel 12 REST API
│   ├── app/
│   │   ├── Events/                  ← Domain/application events
│   │   ├── Jobs/                    ← Background jobs
│   │   ├── Listeners/               ← Event listeners
│   │   ├── Models/                  ← Eloquent models
│   │   └── Http/Controllers/Api/    ← REST API controllers
│   │
│   ├── database/
│   │   ├── migrations/              ← Database schema
│   │   └── seeders/                 ← Demo/initial data
│   │
│   ├── public/
│   ├── storage/
│   ├── routes/
│   │   └── api.php                  ← /api/v1 endpoints
│   ├── Dockerfile
│   └── .dockerignore
│
├── frontend/                        ← Vue 3 SPA
│   ├── src/
│   │   ├── components/              ← Reusable UI components
│   │   ├── views/                   ← Application pages/dashboards
│   │   ├── stores/                  ← Pinia state management
│   │   ├── services/
│   │   │   └── api.js               ← Axios API client
│   │   └── router/                  ← Vue Router + route guards
│   │
│   ├── public/
│   ├── Dockerfile
│   └── .dockerignore
│
├── nginx/
│   └── default.conf                 ← Reverse proxy / SPA / API config
│
├── docker-compose.yml               ← Local/production-style container setup
├── .gitignore
└── README.md
```

---

## 🐳 Docker Architecture

The application is containerized using Docker Compose.

```text
                    Browser
                       │
                       ▼
                ┌─────────────┐
                │    Nginx    │
                │   Port 80   │
                └──────┬──────┘
                       │
             ┌─────────┴─────────┐
             │                   │
             ▼                   ▼
      Vue Static Files       Laravel API
             │                   │
             │                   ▼
             │              PHP-FPM App
             │                   │
             │          ┌────────┴────────┐
             │          │                 │
             │          ▼                 ▼
             │       MySQL 8         Queue Worker
             │
             └──────────────┐
                            ▼
                    Persistent Storage
```

### Docker Services

| Service  | Purpose                                             |
| -------- | --------------------------------------------------- |
| `nginx`  | Serves Vue frontend and routes API/storage requests |
| `app`    | Laravel application running with PHP-FPM            |
| `worker` | Processes Laravel queued jobs                       |
| `db`     | MySQL 8 database                                    |

### Persistent Volumes

Docker volumes are used so important data is not lost when containers are recreated.

```text
hms-db-data    → MySQL database files
hms-storage    → Laravel uploaded/storage files
```

---

## 🛠️ Technology Stack

| Layer                 | Technology                                          |
| --------------------- | --------------------------------------------------- |
| Frontend              | Vue 3, Vite, Tailwind CSS, Pinia, Vue Router, Axios |
| Backend               | Laravel 12, PHP 8.2, Eloquent ORM                   |
| Authentication        | Laravel Sanctum, Bearer Tokens                      |
| Database              | MySQL 8                                             |
| Web Server            | Nginx                                               |
| Application Server    | PHP-FPM                                             |
| Background Processing | Laravel Queue Worker                                |
| Containers            | Docker, Docker Compose                              |
| API                   | REST API with `/api/v1` versioning                  |
| State Management      | Pinia                                               |
| HTTP Client           | Axios                                               |
| Version Control       | Git + GitHub                                        |

---

## 🔐 Authentication & Authorization

The HMS uses token-based authentication through **Laravel Sanctum**.

Different user roles have separate access areas:

* Admin
* Doctor
* Receptionist
* Patient

Protected API routes require authentication and role-based authorization.

Example API structure:

```text
/api/v1/login
/api/v1/register
/api/v1/departments
/api/v1/doctors
/api/v1/appointments
/api/v1/services
/api/v1/events
/api/v1/queue
```

---

## 🏥 Main HMS Features

### 👑 Admin

* Hospital management
* Department management
* Doctor management
* Service management
* Appointment management
* Queue management
* User/role management
* Events and testimonials
* Administrative dashboards
* Activity/audit-related workflows

### 🩺 Doctor

* Doctor dashboard
* Appointment management
* Patient-related workflows
* Queue interaction
* Medical/service workflows
* Notifications

### 🧑 Patient

* Patient registration/login
* Browse departments
* Browse doctors
* View services
* Book appointments
* Appointment management
* Queue-related information
* Event registration
* Patient dashboard

### 🏥 Receptionist

* Reception dashboard
* Appointment handling
* OPD queue management
* Patient assistance workflows
* Operational hospital tasks

---

## 📺 OPD Queue

The HMS includes an OPD queue workflow for managing patient tokens and queue activity.

The queue functionality can be used through the dedicated queue display interface.

```text
Queue → Token → Calling → Patient Flow
```

---

## ⚙️ Background Jobs & Events

The backend uses Laravel events, listeners, and queues for asynchronous processing.

Examples include:

* Appointment-related events
* Queue events
* Emergency-related events
* Appointment notification processing
* Background billing-related jobs

The Dockerized queue worker runs independently from the Laravel application container.

Example:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

The current setup uses the **database queue driver**, so Redis is not required for the initial deployment architecture.

---

## 📁 File Storage

Laravel public storage is used for uploaded files such as user/profile assets.

The storage link is created using:

```bash
php artisan storage:link
```

Docker uses a persistent volume for Laravel storage:

```text
hms-storage
```

Nginx serves public storage files through:

```text
/storage/...
```

This prevents uploaded files from being lost when application containers are recreated.

---

## 🚀 Running the Application Locally

### Option 1 — Traditional Development

#### Backend

```bash
cd backend

php artisan serve --port=8000
```

#### Frontend

Open another terminal:

```bash
cd frontend

npm install
npm run dev
```

The frontend development server normally runs on:

```text
http://localhost:5173
```

---

## 🐳 Running with Docker

From the project root:

```bash
cd F:\Project\HMS
```

Build and start all services:

```bash
docker compose up -d --build
```

Check running services:

```bash
docker compose ps
```

Expected services:

```text
hms-nginx
hms-app
hms-worker
hms-db
```

The Dockerized application is then available through:

```text
http://localhost
```

Nginx acts as the single entry point for the frontend, Laravel API, and uploaded files.

---

## 🗄️ Database Setup

Run Laravel migrations:

```bash
docker compose exec app php artisan migrate
```

Seed demo/initial data:

```bash
docker compose exec app php artisan db:seed
```

Check migration status:

```bash
docker compose exec app php artisan migrate:status
```

---

## 🔧 Useful Docker Commands

### Check containers

```bash
docker compose ps
```

### View application logs

```bash
docker compose logs app
```

### View Nginx logs

```bash
docker compose logs nginx
```

### View queue worker logs

```bash
docker compose logs worker
```

### Follow logs

```bash
docker compose logs -f app
```

### Stop services

```bash
docker compose down
```

### Restart services

```bash
docker compose restart
```

---

## 🌐 Application URLs

When running through the Dockerized Nginx setup:

| Portal                  | URL                              |
| ----------------------- | -------------------------------- |
| 🏠 Home                 | `http://localhost/`              |
| 📺 Live OPD Queue       | `http://localhost/queue-display` |
| 🔑 Login                | `http://localhost/login`         |
| 👑 Admin Dashboard      | `http://localhost/admin`         |
| 🩺 Doctor Dashboard     | `http://localhost/doctor`        |
| 🧑 Patient Portal       | `http://localhost/patient`       |
| 🏥 Receptionist Console | `http://localhost/reception`     |

---

## 🔑 Demo Accounts

The database seeder creates demo users for testing different roles.

Demo accounts are intended for **local/testing environments only**.

> Do not use seeded demo credentials in a real production environment. Production credentials must be changed before deployment.

---

## 🔒 Environment Configuration

Environment files containing secrets are intentionally excluded from Git.

Examples:

```text
.env
.env.docker
backend/.env
frontend/.env
```

Sensitive values such as:

* Application keys
* Database passwords
* Production credentials
* API secrets

must never be committed to GitHub.

For production, environment variables/secrets should be supplied securely through the deployment environment rather than stored in the repository.

---

## 🔄 Git & Deployment Flow

The project follows a production-oriented deployment workflow:

```text
Local Development
       │
       ▼
Local Testing
       │
       ▼
Docker Build
       │
       ▼
Git Commit
       │
       ▼
GitHub
       │
       ▼
AWS EC2
       │
       ▼
Docker Production Deployment
       │
       ▼
Nginx
       │
       ▼
Domain + HTTPS
```

The deployment architecture is designed to support automated CI/CD using **GitHub Actions**.

---

## ☁️ Production Deployment Architecture

The planned production environment uses:

```text
                    Internet
                       │
                       ▼
                 Domain / HTTPS
                       │
                       ▼
                    Nginx
                       │
              ┌────────┴────────┐
              │                 │
              ▼                 ▼
        Vue Frontend       Laravel API
                                │
                    ┌───────────┴───────────┐
                    │                       │
                    ▼                       ▼
                 MySQL                Queue Worker
```

The application can be deployed to an AWS EC2 server using Docker Compose.

Production configuration should use:

* `APP_ENV=production`
* `APP_DEBUG=false`
* Production `APP_URL`
* Secure database credentials
* Rotated application key
* Persistent database storage
* Persistent application storage
* Nginx reverse proxy
* HTTPS/TLS
* Secure firewall rules
* Secure deployment secrets

---

## 🚀 CI/CD

GitHub Actions can be used to automate the deployment process.

A typical CI/CD workflow is:

```text
Developer
   │
   ▼
git push
   │
   ▼
GitHub
   │
   ▼
GitHub Actions
   │
   ├── Test
   ├── Build
   ├── Build Docker Image
   └── Deploy
          │
          ▼
       AWS EC2
          │
          ▼
      Docker Compose
```

Repository secrets should be used for sensitive deployment information instead of committing secrets to the repository.

---

## 🧪 Production Readiness Checklist

Before exposing the application publicly:

* [ ] `APP_ENV=production`
* [ ] `APP_DEBUG=false`
* [ ] Production `APP_URL` configured
* [ ] Application key rotated/generated securely
* [ ] Production database credentials configured
* [ ] Seed/demo passwords changed
* [ ] Git repository contains no secrets
* [ ] Docker images build successfully
* [ ] Database migrations verified
* [ ] Persistent volumes configured
* [ ] Queue worker running
* [ ] Nginx configured
* [ ] Firewall configured
* [ ] Domain configured
* [ ] HTTPS enabled
* [ ] Application logs verified
* [ ] Backup strategy configured
* [ ] CI/CD deployment tested

---

## 📌 Project Status

The HMS currently has a Dockerized local production-style environment consisting of:

* Vue 3 frontend
* Laravel 12 backend
* PHP-FPM
* MySQL 8
* Nginx
* Laravel queue worker
* Persistent Docker volumes
* Git/GitHub source control
* API routing through Nginx
* Laravel storage served through Nginx

The next deployment stage is to configure the production environment on AWS EC2 and then automate deployments using GitHub Actions.

---

## 👩‍💻 Development

Built as a full-stack web application using modern frontend, backend, database, containerization, and deployment practices.

**HealthPulse Hospital & Research Center — Hospital Management System**

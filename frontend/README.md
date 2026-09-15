# HealthPulse HMS — Frontend (Vue 3 SPA)

**Stack:** Vue 3 (Composition API) · Vite · Tailwind CSS · Pinia · Vue Router 4 · Axios · Lucide Icons

## Project Structure

```
frontend/
├── src/
│   ├── components/           # Reusable UI sections
│   │   ├── Navbar.vue
│   │   ├── HeroSection.vue
│   │   ├── FeaturesSection.vue
│   │   ├── DepartmentsSection.vue
│   │   ├── DoctorsSection.vue
│   │   ├── ServicesSection.vue
│   │   ├── EventsSection.vue
│   │   ├── GallerySection.vue
│   │   ├── TestimonialsSection.vue
│   │   ├── FooterSection.vue
│   │   ├── AppointmentBookingModal.vue
│   │   └── EmergencyFloatingBar.vue
│   ├── views/                # Page-level views (matched by router)
│   │   ├── HomeView.vue
│   │   ├── DepartmentsView.vue
│   │   ├── DoctorsView.vue
│   │   ├── ServicesView.vue
│   │   ├── EventsView.vue
│   │   ├── GalleryView.vue
│   │   ├── ContactView.vue
│   │   ├── LoginView.vue
│   │   ├── LiveQueueDisplayView.vue
│   │   ├── AdminDashboardView.vue
│   │   ├── DoctorDashboardView.vue
│   │   ├── PatientDashboardView.vue
│   │   └── ReceptionistDashboardView.vue
│   ├── stores/               # Pinia state management
│   │   ├── authStore.js
│   │   ├── hospitalStore.js
│   │   └── queueStore.js
│   ├── services/
│   │   └── api.js            # Axios instance → http://127.0.0.1:8000/api/v1
│   ├── router/
│   │   └── index.js          # Vue Router 4 with auth meta guards
│   ├── App.vue
│   ├── main.js
│   └── style.css             # Tailwind base + custom scrollbar + animations
├── public/
│   └── assets/images/        # Hero banner & hospital media
├── tailwind.config.js
├── vite.config.js
└── package.json
```

## Dev Server

```bash
npm run dev      # http://localhost:5173
npm run build    # Production build → dist/
```

## Role Portals

| Portal | URL |
| ------ | --- |
| Home (Public SPA) | http://localhost:5173/ |
| Live OPD Queue TV | http://localhost:5173/queue-display |
| Login | http://localhost:5173/login |
| Admin Dashboard | http://localhost:5173/admin |
| Doctor Dashboard | http://localhost:5173/doctor |
| Patient Portal | http://localhost:5173/patient |
| Receptionist Console | http://localhost:5173/reception |

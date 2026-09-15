import { createRouter, createWebHistory } from 'vue-router';
import HomeView from '../views/HomeView.vue';
import DepartmentsView from '../views/DepartmentsView.vue';
import DoctorsView from '../views/DoctorsView.vue';
import ServicesView from '../views/ServicesView.vue';
import EventsView from '../views/EventsView.vue';
import GalleryView from '../views/GalleryView.vue';
import ContactView from '../views/ContactView.vue';
import LiveQueueDisplayView from '../views/LiveQueueDisplayView.vue';
import LoginView from '../views/LoginView.vue';
import PrivacyPolicyView from '../views/PrivacyPolicyView.vue';
import AdminDashboardView from '../views/AdminDashboardView.vue';
import DoctorDashboardView from '../views/DoctorDashboardView.vue';
import PatientDashboardView from '../views/PatientDashboardView.vue';
import ReceptionistDashboardView from '../views/ReceptionistDashboardView.vue';

const routes = [
  {
    path: '/',
    name: 'Home',
    component: HomeView,
  },
  {
    path: '/departments',
    name: 'Departments',
    component: DepartmentsView,
  },
  {
    path: '/doctors',
    name: 'Doctors',
    component: DoctorsView,
  },
  {
    path: '/services',
    name: 'Services',
    component: ServicesView,
  },
  {
    path: '/events',
    name: 'Events',
    component: EventsView,
  },
  {
    path: '/gallery',
    name: 'Gallery',
    component: GalleryView,
  },
  {
    path: '/contact',
    name: 'Contact',
    component: ContactView,
  },
  {
    path: '/privacy',
    name: 'PrivacyPolicy',
    component: PrivacyPolicyView,
  },
  {
    path: '/queue-display',
    name: 'LiveQueueDisplay',
    component: LiveQueueDisplayView,
  },
  {
    path: '/login',
    name: 'Login',
    component: LoginView,
  },
  {
    path: '/admin',
    name: 'AdminDashboard',
    component: AdminDashboardView,
    meta: { requiresAuth: true, role: 'admin' },
  },
  {
    path: '/doctor',
    name: 'DoctorDashboard',
    component: DoctorDashboardView,
    meta: { requiresAuth: true, role: 'doctor' },
  },
  {
    path: '/patient',
    name: 'PatientDashboard',
    component: PatientDashboardView,
    meta: { requiresAuth: true, role: 'patient' },
  },
  {
    path: '/reception',
    alias: '/receptionist',
    name: 'ReceptionistDashboard',
    component: ReceptionistDashboardView,
    meta: { requiresAuth: true, roles: ['receptionist', 'admin'] },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 };
  }
});

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('hp_token');
  let user = null;
  try {
    const raw = localStorage.getItem('hp_user');
    if (raw && raw !== 'undefined' && raw !== 'null') {
      user = JSON.parse(raw);
    }
  } catch (e) {
    user = null;
  }

  if (to.meta.requiresAuth && !token) {
    next({ name: 'Login', query: { redirect: to.fullPath } });
  } else if (to.meta.role && user && user.role !== to.meta.role && user.role !== 'admin') {
    if (user.role === 'doctor') next({ name: 'DoctorDashboard' });
    else if (user.role === 'patient') next({ name: 'PatientDashboard' });
    else if (user.role === 'receptionist') next({ name: 'ReceptionistDashboard' });
    else next({ name: 'Home' });
  } else if (to.meta.roles && user && !to.meta.roles.includes(user.role) && user.role !== 'admin') {
    if (user.role === 'doctor') next({ name: 'DoctorDashboard' });
    else if (user.role === 'patient') next({ name: 'PatientDashboard' });
    else if (user.role === 'receptionist') next({ name: 'ReceptionistDashboard' });
    else next({ name: 'Home' });
  } else {
    next();
  }
});

export default router;

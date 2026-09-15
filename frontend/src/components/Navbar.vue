<script setup>
import { ref, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/authStore';
import { useHospitalStore } from '../stores/hospitalStore';
import {
  Phone, Clock, MapPin, User, LogOut, LayoutDashboard,
  Calendar, ChevronDown, Activity, HeartPulse, Stethoscope,
  Tv, Menu, X, ShieldAlert, Home, Sparkles, Image as ImageIcon,
  Mail, ArrowRight, ShieldCheck, Building
} from 'lucide-vue-next';

const emit = defineEmits(['open-booking']);
const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const hospitalStore = useHospitalStore();

const isMobileMenuOpen = ref(false);
const isDoctorsDropdownOpen = ref(false);
const isDepartmentsDropdownOpen = ref(false);

const getAvatarUrl = (avatar) => {
  if (!avatar) return '/assets/images/default-patient.svg';

  if (avatar.startsWith('http') || avatar.startsWith('data:')) {
    return avatar;
  }

  if (avatar.startsWith('/')) {
    return avatar;
  }

  return '/' + avatar;
};

const onAvatarError = (e) => {
  const currentSrc = e.target.src;
  const avatarPath = authStore.user?.avatar;

  if (avatarPath && !currentSrc.startsWith('data:')) {
    const cleanPath = avatarPath.startsWith('/')
      ? avatarPath.substring(1)
      : avatarPath;

    e.target.src = `/${cleanPath}`;
    return;
  }

  e.target.src = '/assets/images/default-patient.svg';
};

const closeDropdowns = () => {
  isDoctorsDropdownOpen.value = false;
  isDepartmentsDropdownOpen.value = false;
  isMobileMenuOpen.value = false;
};

watch(() => route.path, () => {
  closeDropdowns();
});

const dashboardRoute = computed(() => {
  if (authStore.isAdmin) return '/admin';
  if (authStore.isDoctor) return '/doctor';
  if (authStore.isPatient) return '/patient';
  if (authStore.isReceptionist) return '/reception';

  return '/login';
});

const userDisplayName = computed(() => {
  const name = authStore.user?.name;

  if (!name) return 'User';

  const cleaned = name.replace(/\s*\([^)]*\)/g, '').trim();

  return cleaned || name;
});

const handleLogout = () => {
  authStore.logout();
  closeDropdowns();
  router.push('/');
};

const triggerBooking = () => {
  emit('open-booking');
  closeDropdowns();
};
</script>

<template>
  <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-100 shadow-sm transition-all">

    <!-- Top Emergency & Info Banner -->
    <div class="bg-gradient-to-r from-brand-900 via-brand-800 to-medical-dark text-white text-xs py-2 px-4">
      <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-2">

        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-x-4 gap-y-1 text-center sm:text-left">

          <span class="flex items-center gap-1.5 font-medium text-red-300 animate-pulse text-[11px] sm:text-xs">
            <ShieldAlert class="w-3.5 h-3.5 text-red-400 flex-shrink-0" />

            <span>
              24/7 Trauma Emergency:
              <strong class="text-white">+1 (800) 456-7890</strong>
            </span>
          </span>

          <span class="hidden md:flex items-center gap-1.5 text-slate-300 text-[11px] sm:text-xs">
            <Clock class="w-3.5 h-3.5 text-brand-300 flex-shrink-0" />
            <span>OPD Hours: Mon - Sat 08:00 AM - 08:00 PM</span>
          </span>

          <span class="hidden lg:flex items-center gap-1.5 text-slate-300 text-[11px] sm:text-xs">
            <MapPin class="w-3.5 h-3.5 text-brand-300 flex-shrink-0" />
            <span>450 Wellness Blvd, Medical City, NY</span>
          </span>

        </div>

        <div class="flex items-center gap-3">

          <router-link
            to="/queue-display"
            target="_blank"
            @click="closeDropdowns"
            class="flex items-center gap-1.5 bg-brand-700/60 hover:bg-brand-600 px-2.5 py-1 rounded-full text-[11px] font-semibold text-brand-100 transition-all border border-brand-500/40"
          >
            <Tv class="w-3 h-3 text-emerald-400 animate-pulse" />
            <span>Live OPD TV Display</span>
          </router-link>

          <template v-if="authStore.isAuthenticated">
            <span class="text-brand-200 text-xs hidden sm:inline-flex items-center gap-1.5 truncate max-w-[280px]">
              Hello,
              <strong class="text-white font-bold">
                {{ userDisplayName }}
              </strong>
            </span>
          </template>

        </div>
      </div>
    </div>

    <!-- Main Navigation Bar -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-20">

        <!-- Logo & Branding -->
        <router-link
          to="/"
          @click="closeDropdowns"
          class="flex items-center gap-2.5 sm:gap-3 group min-w-0 mr-2 flex-shrink-0"
        >
          <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-gradient-to-tr from-brand-700 to-brand-500 flex items-center justify-center text-white shadow-md shadow-brand-500/20 group-hover:scale-105 transition-transform flex-shrink-0">
            <HeartPulse class="w-5 h-5 sm:w-6 sm:h-6 animate-pulse" />
          </div>

          <div class="min-w-0">
            <span class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 block leading-tight">
              HealthPulse
            </span>

            <p class="text-[10px] sm:text-[11px] font-semibold text-slate-400 tracking-wider hidden sm:block truncate">
              SMART HEALTHCARE & RESEARCH CENTER
            </p>
          </div>
        </router-link>

        <!-- Desktop Navigation Links -->
        <nav class="hidden lg:flex items-center space-x-1 font-medium text-slate-700 text-sm">

          <router-link
            to="/"
            @click="closeDropdowns"
            class="px-3.5 py-2 rounded-lg hover:text-brand-600 hover:bg-brand-50/70 transition-colors"
            :class="{ 'text-brand-600 font-semibold bg-brand-50/80': route.path === '/' }"
          >
            Home
          </router-link>

          <!-- Doctors Dropdown -->
          <div
            class="relative"
            @mouseenter="isDoctorsDropdownOpen = true"
            @mouseleave="isDoctorsDropdownOpen = false"
          >
            <button
              @click="isDoctorsDropdownOpen = !isDoctorsDropdownOpen"
              class="flex items-center gap-1 px-3.5 py-2 rounded-lg hover:text-brand-600 hover:bg-brand-50/70 transition-colors"
              :class="{ 'text-brand-600 font-semibold': route.path === '/doctors' }"
            >
              Doctors

              <ChevronDown
                class="w-4 h-4 text-slate-400 transition-transform"
                :class="{ 'rotate-180': isDoctorsDropdownOpen }"
              />
            </button>

            <!-- Dropdown Menu -->
            <div
              v-show="isDoctorsDropdownOpen"
              class="absolute top-full left-0 w-80 bg-white rounded-2xl shadow-2xl border border-slate-100 p-3 grid gap-1 transition-all z-50 animate-in fade-in slide-in-from-top-2"
            >
              <div class="px-3 py-1.5 text-xs font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                Specialist Faculties
              </div>

              <router-link
                v-for="dept in hospitalStore.departments"
                :key="dept.id"
                :to="{ path: '/doctors', query: { department_id: dept.id } }"
                @click="closeDropdowns"
                class="px-3 py-2 rounded-xl text-xs hover:bg-brand-50 hover:text-brand-600 transition flex items-center justify-between"
              >
                <span>{{ dept.name }}</span>
                <span class="text-[10px] text-slate-400 font-mono">
                  {{ dept.doctors_count || 'Faculty' }}
                </span>
              </router-link>

              <div class="border-t border-slate-100 pt-2 mt-1">
                <router-link
                  to="/doctors"
                  @click="closeDropdowns"
                  class="block text-center text-xs font-bold text-brand-600 hover:text-brand-700 py-1"
                >
                  View All Specialist Doctors →
                </router-link>
              </div>
            </div>
          </div>

          <!-- Departments Dropdown -->
          <div
            class="relative"
            @mouseenter="isDepartmentsDropdownOpen = true"
            @mouseleave="isDepartmentsDropdownOpen = false"
          >
            <button
              @click="isDepartmentsDropdownOpen = !isDepartmentsDropdownOpen"
              class="flex items-center gap-1 px-3.5 py-2 rounded-lg hover:text-brand-600 hover:bg-brand-50/70 transition-colors"
              :class="{ 'text-brand-600 font-semibold': route.path === '/departments' }"
            >
              Departments

              <ChevronDown
                class="w-4 h-4 text-slate-400 transition-transform"
                :class="{ 'rotate-180': isDepartmentsDropdownOpen }"
              />
            </button>

            <div
              v-show="isDepartmentsDropdownOpen"
              class="absolute top-full left-0 w-72 bg-white rounded-2xl shadow-2xl border border-slate-100 p-3 grid gap-1 transition-all z-50 animate-in fade-in slide-in-from-top-2"
            >
              <router-link
                v-for="dept in hospitalStore.departments"
                :key="dept.id"
                :to="`/departments`"
                @click="closeDropdowns"
                class="px-3 py-2 rounded-xl text-xs hover:bg-brand-50 hover:text-brand-600 transition flex items-center gap-2"
              >
                <Building class="w-3.5 h-3.5 text-brand-500" />
                <span>{{ dept.name }}</span>
              </router-link>

              <div class="border-t border-slate-100 pt-2 mt-1">
                <router-link
                  to="/departments"
                  @click="closeDropdowns"
                  class="block text-center text-xs font-bold text-brand-600 hover:text-brand-700 py-1"
                >
                  Explore All Clinical Wings →
                </router-link>
              </div>
            </div>
          </div>

          <router-link
            to="/services"
            @click="closeDropdowns"
            class="px-3.5 py-2 rounded-lg hover:text-brand-600 hover:bg-brand-50/70 transition-colors"
            :class="{ 'text-brand-600 font-semibold bg-brand-50/80': route.path === '/services' }"
          >
            Services
          </router-link>

          <router-link
            to="/events"
            @click="closeDropdowns"
            class="px-3.5 py-2 rounded-lg hover:text-brand-600 hover:bg-brand-50/70 transition-colors"
            :class="{ 'text-brand-600 font-semibold bg-brand-50/80': route.path === '/events' }"
          >
            Events
          </router-link>

          <router-link
            to="/gallery"
            @click="closeDropdowns"
            class="px-3.5 py-2 rounded-lg hover:text-brand-600 hover:bg-brand-50/70 transition-colors"
            :class="{ 'text-brand-600 font-semibold bg-brand-50/80': route.path === '/gallery' }"
          >
            Gallery
          </router-link>

          <router-link
            to="/contact"
            @click="closeDropdowns"
            class="px-3.5 py-2 rounded-lg hover:text-brand-600 hover:bg-brand-50/70 transition-colors"
            :class="{ 'text-brand-600 font-semibold bg-brand-50/80': route.path === '/contact' }"
          >
            Contact
          </router-link>

        </nav>

        <!-- Right Action Buttons (Desktop) -->
        <div class="hidden lg:flex items-center space-x-3">

          <button
            @click="triggerBooking"
            class="flex items-center gap-2 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white text-xs font-bold uppercase tracking-wider py-2.5 px-4 rounded-xl shadow-md shadow-red-500/20 hover:shadow-red-500/30 transform hover:-translate-y-0.5 transition-all cursor-pointer"
          >
            <Calendar class="w-4 h-4" />
            Book Appointment
          </button>

          <!-- User Authentication State -->
          <template v-if="authStore.isAuthenticated">

            <router-link
              :to="dashboardRoute"
              @click="closeDropdowns"
              class="flex items-center gap-2.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold py-2.5 pl-3.5 pr-5 rounded-xl shadow-md shadow-brand-500/30 hover:shadow-brand-500/40 transition-all transform hover:-translate-y-0.5 cursor-pointer whitespace-nowrap flex-shrink-0"
            >
              <img
                :src="getAvatarUrl(authStore.user?.avatar)"
                @error="onAvatarError"
                class="w-6 h-6 rounded-full object-cover border-2 border-white/90 shadow-xs bg-white flex-shrink-0"
                alt="User avatar"
              />

              <span class="tracking-wide font-bold">
                Dashboard
              </span>
            </router-link>

            <button
              @click="handleLogout"
              title="Logout"
              class="p-2.5 bg-slate-100 hover:bg-red-50 text-slate-500 hover:text-red-600 border border-slate-200 rounded-xl transition-all shadow-xs cursor-pointer flex items-center justify-center flex-shrink-0"
            >
              <LogOut class="w-4 h-4" />
            </button>

          </template>

          <template v-else>

            <router-link
              to="/login"
              @click="closeDropdowns"
              class="flex items-center gap-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold py-3 px-4 rounded-xl transition-all shadow-md"
            >
              <User class="w-4 h-4 text-brand-300" />
              Portal Login
            </router-link>

          </template>
        </div>

        <!-- Mobile Action Buttons & Toggle -->
        <div class="flex items-center lg:hidden space-x-2">

          <button
            @click="triggerBooking"
            class="px-3 py-2 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-1.5 cursor-pointer flex-shrink-0"
          >
            <Calendar class="w-3.5 h-3.5" />
            <span>Book</span>
          </button>

          <button
            @click="isMobileMenuOpen = !isMobileMenuOpen"
            class="p-2 rounded-xl text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 transition active:scale-95 focus:outline-none cursor-pointer flex items-center justify-center flex-shrink-0"
            :aria-expanded="isMobileMenuOpen"
            aria-label="Toggle navigation"
          >
            <Menu v-if="!isMobileMenuOpen" class="w-5 h-5" />
            <X v-else class="w-5 h-5 text-rose-600" />
          </button>

        </div>
      </div>
    </div>

    <!-- Professional Mobile Dropdown -->
    <transition
      enter-active-class="transition-all duration-300 ease-out overflow-hidden"
      enter-from-class="max-h-0 opacity-0"
      enter-to-class="max-h-[90vh] opacity-100"
      leave-active-class="transition-all duration-200 ease-in overflow-hidden"
      leave-from-class="max-h-[90vh] opacity-100"
      leave-to-class="max-h-0 opacity-0"
    >
      <div
        v-if="isMobileMenuOpen"
        class="lg:hidden bg-white border-t border-slate-200 shadow-2xl overflow-y-auto max-h-[85vh]"
      >

        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 space-y-4">

          <!-- Mobile Navigation -->
          <nav class="flex flex-col divide-y divide-slate-100">

            <router-link
              to="/"
              @click="closeDropdowns"
              class="py-3 px-3 text-base font-semibold text-slate-800 hover:text-brand-600 hover:bg-slate-50 rounded-xl transition-colors"
              :class="{ 'text-brand-600 font-bold bg-brand-50/80': route.path === '/' }"
            >
              Home
            </router-link>

            <router-link
              to="/doctors"
              @click="closeDropdowns"
              class="py-3 px-3 text-base font-semibold text-slate-800 hover:text-brand-600 hover:bg-slate-50 rounded-xl transition-colors"
              :class="{ 'text-brand-600 font-bold bg-brand-50/80': route.path === '/doctors' }"
            >
              Doctors
            </router-link>

            <router-link
              to="/departments"
              @click="closeDropdowns"
              class="py-3 px-3 text-base font-semibold text-slate-800 hover:text-brand-600 hover:bg-slate-50 rounded-xl transition-colors"
              :class="{ 'text-brand-600 font-bold bg-brand-50/80': route.path === '/departments' }"
            >
              Departments
            </router-link>

            <router-link
              to="/services"
              @click="closeDropdowns"
              class="py-3 px-3 text-base font-semibold text-slate-800 hover:text-brand-600 hover:bg-slate-50 rounded-xl transition-colors"
              :class="{ 'text-brand-600 font-bold bg-brand-50/80': route.path === '/services' }"
            >
              Services
            </router-link>

            <router-link
              to="/events"
              @click="closeDropdowns"
              class="py-3 px-3 text-base font-semibold text-slate-800 hover:text-brand-600 hover:bg-slate-50 rounded-xl transition-colors"
              :class="{ 'text-brand-600 font-bold bg-brand-50/80': route.path === '/events' }"
            >
              Events
            </router-link>

            <router-link
              to="/gallery"
              @click="closeDropdowns"
              class="py-3 px-3 text-base font-semibold text-slate-800 hover:text-brand-600 hover:bg-slate-50 rounded-xl transition-colors"
              :class="{ 'text-brand-600 font-bold bg-brand-50/80': route.path === '/gallery' }"
            >
              Gallery
            </router-link>

            <router-link
              to="/contact"
              @click="closeDropdowns"
              class="py-3 px-3 text-base font-semibold text-slate-800 hover:text-brand-600 hover:bg-slate-50 rounded-xl transition-colors"
              :class="{ 'text-brand-600 font-bold bg-brand-50/80': route.path === '/contact' }"
            >
              Contact
            </router-link>

            <router-link
              to="/queue-display"
              target="_blank"
              @click="closeDropdowns"
              class="py-3 px-3 text-base font-semibold text-emerald-700 hover:text-emerald-800 hover:bg-emerald-50/60 rounded-xl transition-colors flex items-center justify-between"
            >
              <span>Live OPD TV Display</span>
              <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2.5 py-0.5 rounded-full">
                Live
              </span>
            </router-link>

          </nav>

          <!-- Bottom Actions & User Account -->
          <div class="pt-3 border-t border-slate-100 space-y-2.5">

            <template v-if="authStore.isAuthenticated">

              <div class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-xl">
                <div class="min-w-0 pr-2">
                  <p class="text-xs text-slate-500">
                    Signed in as
                  </p>

                  <p class="text-sm font-bold text-slate-900 truncate">
                    {{ authStore.user?.name }}
                  </p>
                </div>

                <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 bg-brand-100 text-brand-700 rounded-lg">
                  {{ authStore.role }}
                </span>
              </div>

              <div class="grid grid-cols-2 gap-2">

                <router-link
                  :to="dashboardRoute"
                  @click="closeDropdowns"
                  class="py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold text-center shadow transition"
                >
                  Dashboard
                </router-link>

                <button
                  @click="handleLogout"
                  class="py-2.5 bg-slate-100 hover:bg-red-50 text-slate-700 hover:text-red-600 rounded-xl text-xs font-bold text-center border border-slate-200 transition"
                >
                  Sign Out
                </button>

              </div>

            </template>

            <template v-else>

              <router-link
                to="/login"
                @click="closeDropdowns"
                class="block w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold text-center shadow transition"
              >
                Portal Login / Sign In
              </router-link>

            </template>

            <button
              @click="triggerBooking"
              class="w-full py-3 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider text-center shadow-md transition cursor-pointer"
            >
              Book Appointment Consultation
            </button>

          </div>
        </div>
      </div>
    </transition>

  </header>
</template>
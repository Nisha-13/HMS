<script setup>
import { ref, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useHospitalStore } from '../stores/hospitalStore';
import AppointmentBookingModal from '../components/AppointmentBookingModal.vue';
import {
  Calendar,
  Clock,
  MapPin,
  Search,
  Filter,
  Stethoscope,
  Award,
  Star,
  ArrowRight
} from 'lucide-vue-next';

const route = useRoute();
const hospitalStore = useHospitalStore();

const searchQuery = ref('');
const selectedDept = ref(route.query.department_id || '');
const isBookingOpen = ref(false);
const bookingData = ref(null);

watch(() => route.query.department_id, (val) => {
  if (val) {
    selectedDept.value = val;
  }
});

const filteredDoctors = computed(() => {
  return hospitalStore.doctors.filter(doc => {
    const matchesDept =
      !selectedDept.value ||
      doc.doctor_profile?.department_id == selectedDept.value;

    const matchesSearch =
      !searchQuery.value ||
      doc.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      doc.doctor_profile?.specialty
        ?.toLowerCase()
        .includes(searchQuery.value.toLowerCase()) ||
      doc.doctor_profile?.department?.name
        ?.toLowerCase()
        .includes(searchQuery.value.toLowerCase());

    return matchesDept && matchesSearch;
  });
});

const handleBook = (doc) => {
  bookingData.value = {
    doctor_id: doc.id,
    department_id: doc.doctor_profile?.department_id,
  };

  isBookingOpen.value = true;
};

const getAvatarUrl = (avatar) => {
  if (!avatar) {
    return '/assets/images/doc1.jpg';
  }

  if (avatar.startsWith('http') || avatar.startsWith('data:')) {
    return avatar;
  }

  if (avatar.startsWith('/')) {
    return avatar;
  }

  return '/' + avatar;
};

const onDoctorImgError = (e, avatar) => {
  const currentSrc = e.target.src;

  // Use relative URL so the same code works
  // in local Docker and production.
  if (avatar && !currentSrc.startsWith('data:')) {
    const cleanPath = avatar.startsWith('/')
      ? avatar.substring(1)
      : avatar;

    e.target.src = `/${cleanPath}`;
    return;
  }

  // Final fallback image
  e.target.src = '/assets/images/doc1.jpg';
};
</script>

<template>
  <div class="py-16 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- Header -->
      <div class="text-center max-w-3xl mx-auto mb-12">
        <span class="text-xs font-bold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1.5 rounded-full border border-brand-200">
          Medical Directory
        </span>
        <h1 class="text-3xl sm:text-5xl font-extrabold text-slate-900 mt-4 tracking-tight">
          Find A Specialist Physician
        </h1>
        <p class="text-base text-slate-600 mt-3">
          Book direct consultations with senior consultants, surgeons, and specialists across all disciplines.
        </p>
      </div>

      <!-- Filters & Search Bar -->
      <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 mb-12 flex flex-col md:flex-row items-center gap-4">
        <div class="relative flex-1 w-full">
          <Search class="w-5 h-5 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2" />
          <input 
            type="text" 
            v-model="searchQuery"
            placeholder="Search doctor by name, specialty, or condition..."
            class="w-full bg-slate-50 border border-slate-200 rounded-2xl pl-12 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white"
          />
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
          <select 
            v-model="selectedDept"
            class="w-full md:w-64 bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
            <option value="">All Departments</option>
            <option v-for="d in hospitalStore.departments" :key="d.id" :value="d.id">
              {{ d.name }}
            </option>
          </select>

          <button 
            v-if="selectedDept || searchQuery"
            @click="selectedDept = ''; searchQuery = ''"
            class="px-4 py-3 rounded-2xl text-xs font-semibold text-red-600 hover:bg-red-50 border border-red-200 whitespace-nowrap">
            Clear
          </button>
        </div>
      </div>

      <!-- Doctors Grid -->
      <div v-if="filteredDoctors.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div 
          v-for="doc in filteredDoctors" 
          :key="doc.id"
          class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group">
          
          <div class="relative h-64 overflow-hidden bg-slate-100">
            <img 
              :src="getAvatarUrl(doc.avatar)" 
              :alt="doc.name"
              @error="onDoctorImgError($event, doc.avatar)"
              class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500 bg-slate-200"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent"></div>
            
            <div class="absolute top-3 right-3 bg-brand-600 text-white text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">
              {{ doc.doctor_profile?.experience_years }}+ Yrs Exp
            </div>

            <div class="absolute bottom-3 left-3 right-3">
              <span class="text-xs font-semibold text-brand-300 block">
                {{ doc.doctor_profile?.department?.name || 'Department Faculty' }}
              </span>
              <h3 class="text-lg font-bold text-white leading-tight">
                {{ doc.name }}
              </h3>
            </div>
          </div>

          <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
            <div class="space-y-2">
              <p class="text-xs font-semibold text-brand-700">
                {{ doc.doctor_profile?.specialty }}
              </p>
              <p class="text-xs text-slate-500 line-clamp-1">
                {{ doc.doctor_profile?.qualification }}
              </p>

              <div class="space-y-1 text-xs text-slate-600 pt-2 border-t border-slate-100">
                <div class="flex items-center gap-1.5">
                  <MapPin class="w-3.5 h-3.5 text-brand-500" />
                  <span>{{ doc.doctor_profile?.room_no }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <Clock class="w-3.5 h-3.5 text-emerald-500" />
                  <span>{{ doc.doctor_profile?.available_days }} (09:00 - 17:00)</span>
                </div>
              </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
              <div>
                <span class="text-[11px] text-slate-400 block">Fee</span>
                <span class="text-base font-extrabold text-slate-900">${{ doc.doctor_profile?.consultation_fee }}</span>
              </div>

              <button 
                @click="handleBook(doc)"
                class="flex items-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold py-2.5 px-4 rounded-xl shadow-md transition-all">
                <Calendar class="w-3.5 h-3.5" />
                Book OPD
              </button>
            </div>
          </div>

        </div>
      </div>

      <div v-else class="text-center py-20 bg-white rounded-3xl border border-slate-100 p-8">
        <Stethoscope class="w-12 h-12 text-slate-300 mx-auto mb-3" />
        <h3 class="text-lg font-bold text-slate-800">No Specialists Found</h3>
        <p class="text-xs text-slate-500 mt-1">Try resetting the department or search query.</p>
      </div>

    </div>

    <!-- Booking Modal -->
    <AppointmentBookingModal 
      :is-open="isBookingOpen" 
      :initial-data="bookingData"
      @close="isBookingOpen = false"
    />
  </div>
</template>

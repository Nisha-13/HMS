<script setup>
import { ref, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useHospitalStore } from '../stores/hospitalStore';
import AppointmentBookingModal from '../components/AppointmentBookingModal.vue';
import { 
  HeartPulse, Brain, Baby, Activity, Smile, 
  Filter, ScanLine, Wind, ArrowRight, ShieldCheck, User, CheckCircle2, Sparkles
} from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const hospitalStore = useHospitalStore();
const isBookingOpen = ref(false);
const bookingData = ref(null);

const selectedDeptId = ref(route.query.department_id ? Number(route.query.department_id) : null);

watch(() => route.query.department_id, (newVal) => {
  selectedDeptId.value = newVal ? Number(newVal) : null;
});

const filteredDepartments = computed(() => {
  if (!selectedDeptId.value) return hospitalStore.departments;
  return hospitalStore.departments.filter(d => d.id === selectedDeptId.value);
});

const setFilter = (id) => {
  selectedDeptId.value = id;
  if (id) {
    router.replace({ path: '/departments', query: { department_id: id } });
  } else {
    router.replace({ path: '/departments' });
  }
};

const iconMap = {
  Cardiology: HeartPulse,
  Neurologist: Brain,
  Pediatric: Baby,
  'Traumatology & Orthopedics': Activity,
  'Dental Care & Surgery': Smile,
  'Urology & Nephrology': Filter,
  'X-Ray & Radiology': ScanLine,
  'Pulmonology & Chest': Wind,
};

const handleBook = (dept) => {
  bookingData.value = { department_id: dept.id };
  isBookingOpen.value = true;
};
</script>

<template>
  <div class="py-16 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- Page Header -->
      <div class="text-center max-w-3xl mx-auto mb-10">
        <span class="text-xs font-bold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1.5 rounded-full border border-brand-200">
          Medical Specialties & Centers
        </span>
        <h1 class="text-3xl sm:text-5xl font-extrabold text-slate-900 mt-4 tracking-tight">
          Clinical Departments & Specialties
        </h1>
        <p class="text-base text-slate-600 mt-3 leading-relaxed">
          HealthPulse Hospital operates 8 multidisciplinary clinical departments equipped with cutting-edge medical diagnostic suites and dedicated surgical faculty.
        </p>
      </div>

      <!-- Quick Interactive Department Filter Tabs -->
      <div class="flex items-center justify-center flex-wrap gap-2 mb-12">
        <button 
          @click="setFilter(null)"
          class="px-4 py-2 rounded-2xl text-xs font-bold transition-all"
          :class="selectedDeptId === null ? 'bg-brand-600 text-white shadow-lg shadow-brand-500/25 ring-2 ring-brand-400/40' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'">
          All Departments ({{ hospitalStore.departments.length }})
        </button>
        <button 
          v-for="dept in hospitalStore.departments" 
          :key="dept.id"
          @click="setFilter(dept.id)"
          class="px-4 py-2 rounded-2xl text-xs font-bold transition-all flex items-center gap-1.5"
          :class="selectedDeptId === dept.id ? 'bg-brand-600 text-white shadow-lg shadow-brand-500/25 ring-2 ring-brand-400/40' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'">
          <component :is="iconMap[dept.name] || Activity" class="w-3.5 h-3.5" />
          {{ dept.name }}
        </button>
      </div>

      <!-- Filter Active Banner (If filtered to a single dept) -->
      <div v-if="selectedDeptId" class="mb-8 p-4 bg-brand-50 border border-brand-200 rounded-2xl flex items-center justify-between">
        <div class="flex items-center gap-2">
          <Sparkles class="w-5 h-5 text-brand-600" />
          <span class="text-xs font-bold text-brand-900">
            Showing specialty details for: <strong class="text-brand-600">{{ filteredDepartments[0]?.name }}</strong>
          </span>
        </div>
        <button 
          @click="setFilter(null)" 
          class="text-xs font-bold text-brand-600 hover:underline">
          Clear Filter (Show All 8) ✕
        </button>
      </div>

      <!-- Department Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div 
          v-for="dept in filteredDepartments" 
          :key="dept.id"
          class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group">
          
          <div class="relative h-52 overflow-hidden">
            <img 
              :src="'/' + (dept.image || 'assets/images/dep1.jpg')" 
              :alt="dept.name"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/20 to-transparent"></div>
            
            <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between">
              <span class="text-xs font-bold text-white bg-brand-600/90 px-3 py-1 rounded-lg backdrop-blur-md">
                {{ dept.room_floor }}
              </span>
              <span class="text-xs font-semibold text-brand-200 bg-slate-900/80 px-2.5 py-1 rounded-lg backdrop-blur-md">
                {{ dept.doctors_count || 4 }} Specialists
              </span>
            </div>
          </div>

          <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
            <div>
              <div class="flex items-center gap-2.5 mb-2">
                <component :is="iconMap[dept.name] || Activity" class="w-6 h-6 text-brand-600" />
                <h2 class="text-xl font-bold text-slate-900 group-hover:text-brand-600 transition-colors">
                  {{ dept.name }}
                </h2>
              </div>

              <p class="text-sm text-slate-600 leading-relaxed">
                {{ dept.description }}
              </p>
            </div>

            <div class="pt-4 border-t border-slate-100 space-y-3">
              <div class="text-xs text-slate-600 flex items-center gap-2">
                <User class="w-4 h-4 text-brand-500" />
                <span><strong>Faculty Head:</strong> {{ dept.head_doctor_name }}</span>
              </div>

              <div class="flex items-center gap-3">
                <button 
                  @click="handleBook(dept)"
                  class="flex-1 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold uppercase tracking-wider py-3 rounded-xl shadow-md transition-all text-center">
                  Book Department OPD
                </button>
                <router-link 
                  :to="{ path: '/doctors', query: { department_id: dept.id } }"
                  class="p-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-colors"
                  title="View Doctors">
                  <ArrowRight class="w-4 h-4" />
                </router-link>
              </div>
            </div>
          </div>

        </div>
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

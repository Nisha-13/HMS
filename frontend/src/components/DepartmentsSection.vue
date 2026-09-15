<script setup>
import { useHospitalStore } from '../stores/hospitalStore';
import { useRouter } from 'vue-router';
import { 
  HeartPulse, Brain, Baby, Activity, Smile, 
  Filter, ScanLine, Wind, ArrowRight, UserCheck 
} from 'lucide-vue-next';

const emit = defineEmits(['open-booking']);
const router = useRouter();
const hospitalStore = useHospitalStore();

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

const handleBookDept = (dept) => {
  emit('open-booking', { department_id: dept.id });
};
</script>

<template>
  <section class="py-20 bg-white relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- Section Title -->
      <div class="flex flex-col md:flex-row md:items-end justify-between mb-14 gap-4">
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-brand-600 bg-brand-50 px-3 py-1 rounded-full">
            Clinical Excellence
          </span>
          <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2 tracking-tight">
            Specialized Medical Departments
          </h2>
          <p class="text-sm sm:text-base text-slate-600 mt-2 max-w-xl">
            From emergency cardiac interventions to advanced pediatric care, our specialized centers offer dedicated faculty.
          </p>
        </div>

        <router-link 
          to="/departments" 
          class="inline-flex items-center gap-2 text-sm font-bold text-brand-600 hover:text-brand-700">
          Explore All Faculties <ArrowRight class="w-4 h-4" />
        </router-link>
      </div>

      <!-- Departments Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div 
          v-for="dept in hospitalStore.departments" 
          :key="dept.id"
          class="group rounded-3xl border border-slate-100 bg-white overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">
          
          <!-- Image Banner -->
          <div class="relative h-44 overflow-hidden">
            <img 
              :src="'/' + (dept.image || 'assets/images/dep1.jpg')" 
              :alt="dept.name"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent"></div>
            <span class="absolute bottom-3 left-3 text-xs font-bold text-white bg-slate-900/60 backdrop-blur-md px-2.5 py-1 rounded-lg">
              {{ dept.room_floor }}
            </span>
          </div>

          <!-- Content -->
          <div class="p-6 flex-1 flex flex-col justify-between">
            <div>
              <div class="flex items-center gap-2 mb-2">
                <component 
                  :is="iconMap[dept.name] || Activity" 
                  class="w-5 h-5 text-brand-600" 
                />
                <h3 class="text-lg font-bold text-slate-900 group-hover:text-brand-600 transition-colors">
                  {{ dept.name }}
                </h3>
              </div>

              <p class="text-xs text-slate-600 line-clamp-2 mb-4 leading-relaxed">
                {{ dept.description }}
              </p>

              <div class="text-[11px] text-slate-500 border-t border-slate-100 pt-3">
                <span class="font-semibold text-slate-700">Head:</span> {{ dept.head_doctor_name }}
              </div>
            </div>

            <!-- Action -->
            <button 
              @click="handleBookDept(dept)"
              class="mt-5 w-full flex items-center justify-center gap-2 bg-brand-50 hover:bg-brand-600 hover:text-white text-brand-700 text-xs font-bold uppercase tracking-wider py-2.5 rounded-xl transition-all">
              Book Department OPD →
            </button>
          </div>

        </div>
      </div>

    </div>
  </section>
</template>

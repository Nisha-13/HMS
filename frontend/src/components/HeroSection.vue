<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useHospitalStore } from '../stores/hospitalStore';
import { 
  ShieldCheck, Calendar, Clock, UserCheck, Stethoscope, 
  ArrowRight, PhoneCall, Sparkles, Activity, BedDouble, HeartHandshake
} from 'lucide-vue-next';

const emit = defineEmits(['open-booking']);
const router = useRouter();
const hospitalStore = useHospitalStore();

const getTodayString = () => {
  const d = new Date();
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const minDate = computed(() => getTodayString());

// Quick inline booking form state
const selectedDept = ref('');
const patientName = ref('');
const patientPhone = ref('');
const appointmentDate = ref(getTodayString());

const handleQuickBook = () => {
  if (appointmentDate.value < minDate.value) {
    appointmentDate.value = minDate.value;
  }
  emit('open-booking', {
    department_id: selectedDept.value,
    patient_name: patientName.value,
    patient_phone: patientPhone.value,
    appointment_date: appointmentDate.value
  });
};
</script>

<template>
  <section class="relative bg-slate-950 text-white overflow-hidden min-h-[640px] flex items-center">
    <!-- Hero Background Image with Gradient Overlay -->
    <div class="absolute inset-0 z-0">
      <img 
        src="/assets/images/healthpulse_hero_banner.jpg" 
        alt="HealthPulse Specialist Medical Team" 
        class="w-full h-full object-cover object-center filter brightness-[0.45] contrast-105 transform scale-105 animate-pulse-slow"
      />
      <!-- Deep Medical Color Overlay -->
      <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-900/85 to-brand-950/70"></div>
      <div class="absolute inset-0 bg-radial-gradient from-transparent via-slate-950/60 to-slate-950"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24 w-full">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
        
        <!-- Left Hero Content -->
        <div class="lg:col-span-7 space-y-6">
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-brand-500/20 border border-brand-400/30 text-brand-300 text-[11px] sm:text-xs font-semibold backdrop-blur-md max-w-full">
            <Sparkles class="w-3.5 h-3.5 text-brand-400 flex-shrink-0" />
            <span class="truncate">Smart Care • Cutting-Edge Surgery • 24/7 Trauma Level 1</span>
          </div>

          <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-tight">
            Compassionate Care, <br />
            <span class="bg-gradient-to-r from-brand-400 via-cyan-300 to-emerald-400 bg-clip-text text-transparent">
              Advanced Healing.
            </span>
          </h1>

          <p class="text-base sm:text-lg text-slate-300 max-w-2xl font-light leading-relaxed">
            Welcome to <strong class="text-white font-semibold">HealthPulse Hospital</strong>. Our team of world-class surgeons, diagnostic specialists, and compassionate nurses provide 24/7 intelligent healthcare tailored to your family's wellness.
          </p>

          <!-- Action Buttons -->
          <div class="flex flex-wrap items-center gap-4 pt-2">
            <button 
              @click="$emit('open-booking')"
              class="flex items-center gap-2 bg-gradient-to-r from-brand-600 to-brand-500 hover:from-brand-500 hover:to-brand-400 text-white text-sm font-bold py-3.5 px-6 rounded-xl shadow-xl shadow-brand-500/30 transform hover:-translate-y-0.5 transition-all">
              <Calendar class="w-4 h-4" />
              Book An Appointment
              <ArrowRight class="w-4 h-4" />
            </button>

            <router-link 
              to="/doctors"
              class="flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white text-sm font-semibold py-3.5 px-6 rounded-xl backdrop-blur-md border border-white/20 transition-all">
              <Stethoscope class="w-4 h-4 text-cyan-300" />
              Find A Specialist
            </router-link>
          </div>

          <!-- Trust Badges -->
          <div class="pt-6 border-t border-white/10 flex flex-wrap items-center gap-6 text-xs text-slate-300">
            <div class="flex items-center gap-2">
              <ShieldCheck class="w-4 h-4 text-emerald-400" />
              <span>JCI Accredited Hospital</span>
            </div>
            <div class="flex items-center gap-2">
              <Clock class="w-4 h-4 text-cyan-400" />
              <span>3-Min Emergency Triage</span>
            </div>
            <div class="flex items-center gap-2">
              <UserCheck class="w-4 h-4 text-brand-400" />
              <span>50+ Board Certified Doctors</span>
            </div>
          </div>
        </div>

        <!-- Right Side: Quick OPD Booking Card -->
        <div class="lg:col-span-5">
          <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-brand-500/20 rounded-full blur-3xl -z-10"></div>
            
            <div class="flex items-center justify-between pb-4 border-b border-white/15 mb-6">
              <div>
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                  <Activity class="w-5 h-5 text-emerald-400" />
                  Quick OPD Appointment
                </h3>
                <p class="text-xs text-slate-300 mt-0.5">Instant confirmation & digital token dispenser</p>
              </div>
              <span class="bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 text-[10px] font-bold px-2 py-1 rounded-full uppercase">
                Live Slots Open
              </span>
            </div>

            <form @submit.prevent="handleQuickBook" class="space-y-4">
              <div>
                <label class="block text-xs font-semibold text-slate-200 uppercase tracking-wider mb-1.5">
                  Select Specialty Department
                </label>
                <select 
                  v-model="selectedDept"
                  required
                  class="w-full bg-slate-900/80 border border-white/20 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all">
                  <option value="" disabled>Choose Department</option>
                  <option v-for="d in hospitalStore.departments" :key="d.id" :value="d.id" class="bg-slate-900 text-white">
                    {{ d.name }}
                  </option>
                </select>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-200 uppercase tracking-wider mb-1.5">
                    Patient Name
                  </label>
                  <input 
                    type="text" 
                    v-model="patientName"
                    required
                    placeholder="e.g. John Doe"
                    class="w-full bg-slate-900/80 border border-white/20 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all placeholder:text-slate-500"
                  />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-200 uppercase tracking-wider mb-1.5">
                    Phone Number
                  </label>
                  <input 
                    type="tel" 
                    v-model="patientPhone"
                    required
                    placeholder="+1 (555) 000-0000"
                    class="w-full bg-slate-900/80 border border-white/20 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all placeholder:text-slate-500"
                  />
                </div>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-200 uppercase tracking-wider mb-1.5">
                  Preferred Date
                </label>
                <input 
                  type="date" 
                  :min="minDate"
                  v-model="appointmentDate"
                  required
                  class="w-full bg-slate-900/80 border border-white/20 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all"
                />
              </div>

              <button 
                type="submit"
                class="w-full bg-gradient-to-r from-medical-red to-rose-600 hover:from-rose-600 hover:to-red-700 text-white font-bold text-sm uppercase tracking-wider py-4 rounded-xl shadow-lg shadow-red-500/30 transition-all transform hover:-translate-y-0.5">
                Proceed To Slot Selection →
              </button>

              <p class="text-center text-[11px] text-slate-400">
                🔒 Secure HIPPA/GDPR Compliant Medical Registry
              </p>
            </form>
          </div>
        </div>

      </div>

      <!-- Bottom KPI Live Stats Bar -->
      <div class="mt-14 grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
        <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-4 sm:p-5 flex items-center gap-4">
          <div class="p-3 rounded-xl bg-brand-500/20 text-brand-400 border border-brand-500/30">
            <UserCheck class="w-6 h-6" />
          </div>
          <div>
            <div class="text-2xl sm:text-3xl font-extrabold text-white">
              {{ hospitalStore.stats?.doctors ? hospitalStore.stats.doctors + '+' : '50+' }}
            </div>
            <div class="text-xs text-slate-300">Specialist Physicians</div>
          </div>
        </div>

        <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-4 sm:p-5 flex items-center gap-4">
          <div class="p-3 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
            <HeartHandshake class="w-6 h-6" />
          </div>
          <div>
            <div class="text-2xl sm:text-3xl font-extrabold text-white">
              {{ hospitalStore.stats?.patient_satisfaction || '99.4%' }}
            </div>
            <div class="text-xs text-slate-300">Patient Recovery Rate</div>
          </div>
        </div>

        <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-4 sm:p-5 flex items-center gap-4">
          <div class="p-3 rounded-xl bg-cyan-500/20 text-cyan-400 border border-cyan-500/30">
            <BedDouble class="w-6 h-6" />
          </div>
          <div>
            <div class="text-2xl sm:text-3xl font-extrabold text-white">
              {{ hospitalStore.stats?.beds ? hospitalStore.stats.beds + '+' : '160+' }}
            </div>
            <div class="text-xs text-slate-300">Smart ICU & Ward Beds</div>
          </div>
        </div>

        <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-4 sm:p-5 flex items-center gap-4">
          <div class="p-3 rounded-xl bg-rose-500/20 text-rose-400 border border-rose-500/30">
            <PhoneCall class="w-6 h-6" />
          </div>
          <div>
            <div class="text-2xl sm:text-3xl font-extrabold text-white">24/7</div>
            <div class="text-xs text-slate-300">Emergency & Cath Lab</div>
          </div>
        </div>
      </div>

    </div>
  </section>
</template>

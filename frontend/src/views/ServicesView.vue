<script setup>
import { ref } from 'vue';
import { useHospitalStore } from '../stores/hospitalStore';
import AppointmentBookingModal from '../components/AppointmentBookingModal.vue';
import { ArrowRight, ShieldCheck, CheckCircle2 } from 'lucide-vue-next';

const hospitalStore = useHospitalStore();
const isBookingOpen = ref(false);
const selectedServiceData = ref(null);

const openBookingForService = (service) => {
  selectedServiceData.value = {
    service: service,
    mode: 'service'
  };
  isBookingOpen.value = true;
};
</script>

<template>
  <div class="py-16 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-3xl mx-auto mb-16">
        <span class="text-xs font-bold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1.5 rounded-full border border-brand-200">
          Hospital Facilities
        </span>
        <h1 class="text-3xl sm:text-5xl font-extrabold text-slate-900 mt-4 tracking-tight">
          Clinical Services & Medical Technologies
        </h1>
        <p class="text-base text-slate-600 mt-3">
          Discover our comprehensive healthcare services designed for rapid trauma intervention, accurate diagnostics, and personalized patient recovery.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div 
          v-for="service in hospitalStore.services" 
          :key="service.id"
          class="bg-white rounded-3xl overflow-hidden border border-slate-100 shadow-sm hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group">
          
          <div class="relative h-56 overflow-hidden">
            <img 
              :src="'/' + (service.image || 'assets/images/service1.jpg')" 
              :alt="service.title"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"></div>
            <span class="absolute top-4 left-4 text-xs font-bold text-white bg-brand-600 px-3 py-1 rounded-full shadow">
              {{ service.department_name }}
            </span>
          </div>

          <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
            <div>
              <h2 class="text-xl font-bold text-slate-900 group-hover:text-brand-600 transition-colors leading-snug">
                {{ service.title }}
              </h2>
              <p class="text-sm text-slate-600 mt-2.5 leading-relaxed">
                {{ service.description }}
              </p>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
              <span class="text-sm font-bold text-slate-900">
                <span v-if="service.starting_price > 0">From ${{ service.starting_price }}</span>
                <span v-else class="text-emerald-600">24/7 Available</span>
              </span>

              <button 
                @click="openBookingForService(service)"
                class="inline-flex items-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold py-2.5 px-4 rounded-xl shadow-md transition-all">
                Book Procedure <ArrowRight class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

        </div>
      </div>

    </div>

    <!-- Booking Modal -->
    <AppointmentBookingModal 
      :is-open="isBookingOpen" 
      :initial-data="selectedServiceData"
      @close="isBookingOpen = false"
    />
  </div>
</template>

<script setup>
import { useHospitalStore } from '../stores/hospitalStore';
import { ArrowRight, CheckCircle2, Stethoscope, ShieldCheck } from 'lucide-vue-next';

const emit = defineEmits(['open-booking']);
const hospitalStore = useHospitalStore();
</script>

<template>
  <section class="py-20 bg-slate-50 relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-3xl mx-auto mb-16">
        <span class="text-xs font-bold uppercase tracking-wider text-brand-600 bg-brand-50 px-3 py-1 rounded-full">
          Comprehensive Services
        </span>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2 tracking-tight">
          Clinical Facilities & Diagnostic Infrastructure
        </h2>
        <p class="text-base text-slate-600 mt-2">
          From level-1 trauma resuscitation to digital cath labs, discover how HealthPulse provides end-to-end patient care.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div 
          v-for="service in hospitalStore.services" 
          :key="service.id"
          class="bg-white rounded-3xl overflow-hidden border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group">
          
          <div class="relative h-48 overflow-hidden">
            <img 
              :src="'/' + (service.image || 'assets/images/service1.jpg')" 
              :alt="service.title"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent"></div>
            <span class="absolute top-3 left-3 text-xs font-bold text-white bg-brand-600 px-3 py-1 rounded-full shadow">
              {{ service.department_name }}
            </span>
          </div>

          <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
            <div>
              <h3 class="text-lg font-bold text-slate-900 group-hover:text-brand-600 transition-colors leading-snug">
                {{ service.title }}
              </h3>
              <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                {{ service.description }}
              </p>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
              <span class="text-xs font-bold text-slate-900">
                <span v-if="service.starting_price > 0">From ${{ service.starting_price }}</span>
                <span v-else class="text-emerald-600">24/7 Available</span>
              </span>

              <button 
                @click="$emit('open-booking', { service: service, mode: 'service' })"
                class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 hover:text-brand-700 bg-brand-50 hover:bg-brand-100 py-2 px-3.5 rounded-xl transition-colors">
                Book Procedure <ArrowRight class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

        </div>
      </div>

    </div>
  </section>
</template>

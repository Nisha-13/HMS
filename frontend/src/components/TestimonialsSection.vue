<script setup>
import { useHospitalStore } from '../stores/hospitalStore';
import { Star, Quote, Heart } from 'lucide-vue-next';

const hospitalStore = useHospitalStore();
</script>

<template>
  <section class="py-20 bg-slate-50 relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-3xl mx-auto mb-16">
        <span class="text-xs font-bold uppercase tracking-wider text-brand-600 bg-brand-50 px-3 py-1 rounded-full">
          Stories of Hope & Recovery
        </span>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2 tracking-tight">
          What Our Patients Say About Us
        </h2>
        <p class="text-base text-slate-600 mt-2">
          Real experiences and recovery journeys from patients who entrusted their health to HealthPulse Hospital.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div 
          v-for="test in hospitalStore.testimonials" 
          :key="test.id"
          class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between relative">
          
          <Quote class="w-10 h-10 text-brand-100 absolute top-6 right-6 -z-0" />

          <div class="relative z-10 space-y-4">
            <div class="flex items-center gap-1 text-amber-400">
              <Star v-for="s in test.rating" :key="s" class="w-4 h-4 fill-amber-400" />
            </div>

            <p class="text-sm text-slate-600 leading-relaxed italic">
              "{{ test.comment }}"
            </p>
          </div>

          <div class="pt-6 border-t border-slate-100 flex items-center gap-3.5 mt-6">
            <img 
              :src="'/' + (test.avatar || 'assets/images/testimonail.jpg')" 
              class="w-12 h-12 rounded-full object-cover border-2 border-brand-200"
            />
            <div>
              <h4 class="text-sm font-bold text-slate-900">{{ test.patient_name }}</h4>
              <p class="text-xs text-brand-600 font-medium">{{ test.patient_role }}</p>
            </div>
          </div>

        </div>
      </div>

    </div>
  </section>
</template>

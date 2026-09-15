<script setup>
import { ref } from 'vue';
import { Eye } from 'lucide-vue-next';

const galleryItems = [
  { src: '/assets/images/gallery1.jpg', title: 'Modular Operating Theater (OT 1)', category: 'Surgical Suites' },
  { src: '/assets/images/gallery2.jpg', title: 'Digital Intensive Care Unit (ICU)', category: 'Critical Care' },
  { src: '/assets/images/gallery4.jpg', title: 'High-Tech Cardiac Catheterization Lab', category: 'Cardiology' },
  { src: '/assets/images/gallery5.jpg', title: 'Executive Inpatient Private Suite', category: 'Patient Rooms' },
  { src: '/assets/images/gallery6.jpg', title: '3-Tesla Quiet MRI Diagnostic Bay', category: 'Radiology' },
  { src: '/assets/images/gallery7.jpg', title: 'Modern Hospital Reception & OPD Lobby', category: 'Infrastructure' },
  { src: '/assets/images/gallery8.jpg', title: 'Neonatal Intensive Care Unit (NICU)', category: 'Pediatrics' },
  { src: '/assets/images/healthpulse_hero_banner.jpg', title: 'HealthPulse Main Medical Campus', category: 'Campus' },
];

const selectedItem = ref(null);
</script>

<template>
  <div class="py-16 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-3xl mx-auto mb-16">
        <span class="text-xs font-bold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1.5 rounded-full border border-brand-200">
          Campus Tour
        </span>
        <h1 class="text-3xl sm:text-5xl font-extrabold text-slate-900 mt-4 tracking-tight">
          Hospital Infrastructure & Facility Gallery
        </h1>
        <p class="text-base text-slate-600 mt-3">
          Explore our smart operating theaters, peaceful inpatient wards, high-precision imaging suites, and world-class healthcare architecture.
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div 
          v-for="(item, idx) in galleryItems" 
          :key="idx"
          @click="selectedItem = item"
          class="relative h-64 rounded-3xl overflow-hidden cursor-pointer group border border-slate-200 shadow-sm hover:shadow-2xl transition-all duration-300">
          <img 
            :src="item.src" 
            :alt="item.title"
            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/30 to-transparent opacity-80 group-hover:opacity-95 transition-opacity"></div>
          
          <div class="absolute bottom-4 left-4 right-4 flex items-end justify-between">
            <div>
              <span class="text-[10px] font-bold uppercase tracking-wider text-brand-400 bg-brand-950/80 px-2 py-0.5 rounded">
                {{ item.category }}
              </span>
              <h3 class="text-sm font-bold text-white mt-1 leading-snug">
                {{ item.title }}
              </h3>
            </div>
            <div class="w-8 h-8 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center text-white group-hover:bg-brand-500 transition-colors">
              <Eye class="w-4 h-4" />
            </div>
          </div>
        </div>
      </div>

      <!-- Lightbox Modal -->
      <div 
        v-if="selectedItem" 
        @click="selectedItem = null"
        class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md flex items-center justify-center p-4">
        <div class="max-w-4xl w-full bg-slate-900 rounded-3xl overflow-hidden border border-slate-700 p-3 text-center">
          <img :src="selectedItem.src" class="w-full max-h-[80vh] object-contain rounded-2xl mx-auto" />
          <div class="p-4">
            <h3 class="text-xl font-bold text-white">{{ selectedItem.title }}</h3>
            <p class="text-xs text-brand-300">{{ selectedItem.category }}</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

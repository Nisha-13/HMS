<script setup>
import { ref } from 'vue';
import { Eye, Building, Sparkles } from 'lucide-vue-next';

const images = [
  { src: '/assets/images/gallery1.jpg', title: 'Advanced Surgical Operation Theater (OT 1)', category: 'Surgery' },
  { src: '/assets/images/gallery2.jpg', title: 'Digital Intensive Care Unit (ICU)', category: 'Critical Care' },
  { src: '/assets/images/gallery4.jpg', title: 'Cardiac Catheterization Suite', category: 'Cardiology' },
  { src: '/assets/images/gallery5.jpg', title: 'Executive Inpatient Private Suite', category: 'Wards' },
  { src: '/assets/images/gallery6.jpg', title: '3-Tesla MRI & CT Diagnostic Bay', category: 'Radiology' },
  { src: '/assets/images/gallery7.jpg', title: 'Modern Hospital Reception & OPD Lobby', category: 'Infrastructure' },
];

const selectedImage = ref(null);
</script>

<template>
  <section class="py-20 bg-slate-900 text-white relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-3xl mx-auto mb-16">
        <span class="text-xs font-bold uppercase tracking-wider text-brand-400 bg-brand-500/20 px-3 py-1 rounded-full border border-brand-500/30">
          State-Of-The-Art Infrastructure
        </span>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white mt-3 tracking-tight">
          Hospital Gallery & Clinical Facilities
        </h2>
        <p class="mt-3 text-base text-slate-300">
          A visual tour through our world-class surgical theaters, high-dependency units, and patient care environments.
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="(img, idx) in images" 
          :key="idx"
          @click="selectedImage = img"
          class="relative h-64 rounded-3xl overflow-hidden cursor-pointer group border border-slate-700/60 shadow-xl">
          <img 
            :src="img.src" 
            :alt="img.title"
            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/30 to-transparent opacity-80 group-hover:opacity-95 transition-opacity"></div>
          
          <div class="absolute bottom-4 left-4 right-4 flex items-end justify-between">
            <div>
              <span class="text-[10px] font-bold uppercase tracking-wider text-brand-400 bg-brand-950/80 px-2 py-0.5 rounded">
                {{ img.category }}
              </span>
              <h4 class="text-sm font-bold text-white mt-1 leading-snug">
                {{ img.title }}
              </h4>
            </div>
            <div class="w-8 h-8 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center text-white group-hover:bg-brand-500 transition-colors">
              <Eye class="w-4 h-4" />
            </div>
          </div>
        </div>
      </div>

      <!-- Lightbox Modal -->
      <div 
        v-if="selectedImage" 
        @click="selectedImage = null"
        class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md flex items-center justify-center p-4">
        <div class="max-w-4xl w-full bg-slate-900 rounded-3xl overflow-hidden border border-slate-700 p-2 relative">
          <img :src="selectedImage.src" class="w-full max-h-[80vh] object-contain rounded-2xl" />
          <div class="p-4 text-center">
            <h3 class="text-lg font-bold text-white">{{ selectedImage.title }}</h3>
            <p class="text-xs text-brand-300">{{ selectedImage.category }}</p>
          </div>
        </div>
      </div>

    </div>
  </section>
</template>

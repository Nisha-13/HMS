<script setup>
import { PhoneCall, Calendar, Activity, Tv } from 'lucide-vue-next';

const emit = defineEmits(['open-booking']);
</script>

<template>
  <div class="fixed bottom-4 right-4 z-40 flex flex-col items-end space-y-2.5 max-w-[calc(100vw-2rem)]">
    <router-link 
      to="/queue-display" 
      target="_blank"
      class="flex items-center gap-2 bg-slate-900/90 hover:bg-slate-900 text-white text-[11px] sm:text-xs font-bold py-2 sm:py-2.5 px-3.5 sm:px-4 rounded-full shadow-2xl backdrop-blur-md border border-slate-700 transition-all transform hover:scale-105 max-w-full">
      <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping flex-shrink-0"></span>
      <Tv class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-emerald-400 flex-shrink-0" />
      <span class="truncate">Live OPD Queue Board</span>
    </router-link>

    <button 
      @click="$emit('open-booking')"
      class="flex items-center gap-1.5 sm:gap-2 bg-gradient-to-r from-medical-red to-rose-600 hover:from-rose-600 hover:to-red-700 text-white text-[11px] sm:text-xs font-extrabold uppercase tracking-wider py-2.5 sm:py-3 px-4 sm:px-5 rounded-full shadow-2xl shadow-red-600/40 border border-red-400/40 transition-all transform hover:scale-105 animate-bounce-subtle max-w-full">
      <Calendar class="w-3.5 h-3.5 sm:w-4 sm:h-4 flex-shrink-0" />
      <span class="truncate">Book Consultation</span>
    </button>
  </div>
</template>

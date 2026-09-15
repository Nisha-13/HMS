<script setup>
import { ref } from 'vue';
import { useHospitalStore } from '../stores/hospitalStore';
import EventRegistrationModal from '../components/EventRegistrationModal.vue';
import { Calendar, MapPin, Clock, Users, ArrowRight, Sparkles } from 'lucide-vue-next';

const hospitalStore = useHospitalStore();
const isRegModalOpen = ref(false);
const selectedEvent = ref(null);

const handleOpenRegistration = (event) => {
  selectedEvent.value = event;
  isRegModalOpen.value = true;
};
</script>

<template>
  <div class="py-16 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-3xl mx-auto mb-16">
        <span class="text-xs font-bold uppercase tracking-wider text-medical-teal bg-teal-50 px-3.5 py-1.5 rounded-full border border-teal-200">
          Outreach & Initiatives
        </span>
        <h1 class="text-3xl sm:text-5xl font-extrabold text-slate-900 mt-4 tracking-tight">
          Health Camps, Workshops & Medical Drives
        </h1>
        <p class="text-base text-slate-600 mt-3">
          Join our mission to promote community wellness through free screening camps, international clinical symposiums, and lifesaving donation drives.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div 
          v-for="event in hospitalStore.events" 
          :key="event.id"
          class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group">
          
          <div class="relative h-56 overflow-hidden">
            <img 
              :src="'/' + (event.image || 'assets/images/Event1.jpg')" 
              :alt="event.title"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent"></div>
            <span class="absolute top-4 left-4 text-xs font-bold uppercase text-white bg-medical-red px-3 py-1 rounded-full shadow">
              {{ event.category }}
            </span>
          </div>

          <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
            <div>
              <div class="flex items-center gap-1.5 text-xs font-semibold text-brand-600 mb-2">
                <Calendar class="w-4 h-4" />
                <span>{{ event.event_date }}</span>
              </div>

              <h2 class="text-xl font-bold text-slate-900 leading-snug group-hover:text-brand-600 transition-colors">
                {{ event.title }}
              </h2>

              <p class="text-sm text-slate-600 mt-2.5 leading-relaxed">
                {{ event.description }}
              </p>
            </div>

            <div class="space-y-2 text-xs text-slate-500 pt-4 border-t border-slate-100">
              <div class="flex items-center gap-2">
                <Clock class="w-4 h-4 text-slate-400" />
                <span>{{ event.time_slot }}</span>
              </div>
              <div class="flex items-center gap-2">
                <MapPin class="w-4 h-4 text-slate-400" />
                <span>{{ event.location }}</span>
              </div>
              <div class="flex items-center gap-2 text-slate-700 font-semibold pt-1">
                <Users class="w-4 h-4 text-brand-500" />
                <span>Organizer: {{ event.organizer }}</span>
              </div>

              <button 
                @click="handleOpenRegistration(event)"
                class="w-full mt-3 bg-teal-50 hover:bg-teal-700 hover:text-white text-teal-800 text-xs font-bold uppercase tracking-wider py-3 rounded-xl transition-all text-center border border-teal-200 hover:border-teal-700 shadow-sm cursor-pointer">
                Register For Free Camp →
              </button>
            </div>
          </div>

        </div>
      </div>

    </div>

    <!-- Free Camp Registration Modal -->
    <EventRegistrationModal 
      :is-open="isRegModalOpen" 
      :event="selectedEvent"
      @close="isRegModalOpen = false"
    />
  </div>
</template>

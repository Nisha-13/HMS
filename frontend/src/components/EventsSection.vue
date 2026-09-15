<script setup>
import { ref } from 'vue';
import { useHospitalStore } from '../stores/hospitalStore';
import EventRegistrationModal from './EventRegistrationModal.vue';
import { Calendar, MapPin, Clock, Users, ArrowRight } from 'lucide-vue-next';

const hospitalStore = useHospitalStore();
const isRegModalOpen = ref(false);
const selectedEvent = ref(null);

const handleOpenRegistration = (event) => {
  selectedEvent.value = event;
  isRegModalOpen.value = true;
};
</script>

<template>
  <section class="py-20 bg-white relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="flex flex-col md:flex-row md:items-end justify-between mb-14 gap-4">
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-medical-teal bg-teal-50 px-3 py-1 rounded-full border border-teal-200">
            Community & Outreach
          </span>
          <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2 tracking-tight">
            Upcoming Health Camps & Medical Events
          </h2>
          <p class="text-base text-slate-600 mt-2 max-w-xl">
            Join our free screening drives, international healthcare symposiums, and community wellness workshops.
          </p>
        </div>

        <router-link 
          to="/events" 
          class="inline-flex items-center gap-2 text-sm font-bold text-brand-600 hover:text-brand-700">
          View All Camps <ArrowRight class="w-4 h-4" />
        </router-link>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div 
          v-for="event in hospitalStore.events" 
          :key="event.id"
          class="rounded-3xl border border-slate-100 bg-white overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group">
          
          <div class="relative h-44 overflow-hidden">
            <img 
              :src="'/' + (event.image || 'assets/images/Event1.jpg')" 
              :alt="event.title"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"></div>
            <span class="absolute top-3 left-3 text-[10px] font-bold uppercase text-white bg-medical-red px-2.5 py-1 rounded-full shadow">
              {{ event.category }}
            </span>
          </div>

          <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
            <div>
              <div class="flex items-center gap-1.5 text-xs font-semibold text-brand-600 mb-1.5">
                <Calendar class="w-3.5 h-3.5" />
                <span>{{ event.event_date }}</span>
              </div>

              <h3 class="text-base font-bold text-slate-900 leading-snug group-hover:text-brand-600 transition-colors">
                {{ event.title }}
              </h3>

              <p class="text-xs text-slate-600 line-clamp-2 mt-2 leading-relaxed">
                {{ event.description }}
              </p>
            </div>

            <div class="space-y-2 text-[11px] text-slate-500 pt-3 border-t border-slate-100">
              <div class="flex items-center gap-1">
                <Clock class="w-3 h-3 text-slate-400" />
                <span>{{ event.time_slot }}</span>
              </div>
              <div class="flex items-center gap-1">
                <MapPin class="w-3 h-3 text-slate-400" />
                <span class="truncate">{{ event.location }}</span>
              </div>
              <button 
                @click="handleOpenRegistration(event)"
                class="w-full mt-2 bg-teal-50 hover:bg-teal-700 hover:text-white text-teal-800 text-xs font-bold py-2 rounded-xl transition-all border border-teal-200 hover:border-teal-700 text-center cursor-pointer">
                Register Free →
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
  </section>
</template>

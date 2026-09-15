<script setup>
import { onMounted, ref } from 'vue';
import { useHospitalStore } from './stores/hospitalStore';
import { useAuthStore } from './stores/authStore';
import Navbar from './components/Navbar.vue';
import FooterSection from './components/FooterSection.vue';
import AppointmentBookingModal from './components/AppointmentBookingModal.vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const hospitalStore = useHospitalStore();
const authStore = useAuthStore();

const isBookingModalOpen = ref(false);
const bookingModalData = ref(null);

const handleGlobalBooking = (data) => {
  bookingModalData.value = data || null;
  isBookingModalOpen.value = true;
};

onMounted(() => {
  hospitalStore.fetchAllPublicData();
  authStore.fetchMe();
});
</script>

<template>
  <div class="min-h-screen bg-white flex flex-col font-sans antialiased text-slate-800 overflow-x-hidden w-full max-w-full">
    <!-- Navbar (Hidden on TV Signage mode) -->
    <Navbar 
      v-if="route.path !== '/queue-display'" 
      @open-booking="handleGlobalBooking" 
    />

    <!-- Router View -->
    <main class="flex-1 w-full max-w-full overflow-x-hidden">
      <router-view @open-booking="handleGlobalBooking" />
    </main>

    <!-- Footer (Hidden on TV Signage mode) -->
    <FooterSection 
      v-if="route.path !== '/queue-display'" 
      @open-booking="handleGlobalBooking" 
    />

    <!-- Global Booking Modal -->
    <AppointmentBookingModal 
      :is-open="isBookingModalOpen" 
      :initial-data="bookingModalData"
      @close="isBookingModalOpen = false"
    />
  </div>
</template>

<style>
@keyframes pulseSlow {
  0%, 100% { transform: scale(1.02); }
  50% { transform: scale(1.05); }
}

.animate-pulse-slow {
  animation: pulseSlow 16s ease-in-out infinite alternate;
}
</style>

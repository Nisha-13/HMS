<script setup>
import { ref } from 'vue';
import HeroSection from '../components/HeroSection.vue';
import FeaturesSection from '../components/FeaturesSection.vue';
import DepartmentsSection from '../components/DepartmentsSection.vue';
import DoctorsSection from '../components/DoctorsSection.vue';
import ServicesSection from '../components/ServicesSection.vue';
import EventsSection from '../components/EventsSection.vue';
import GallerySection from '../components/GallerySection.vue';
import TestimonialsSection from '../components/TestimonialsSection.vue';
import AppointmentBookingModal from '../components/AppointmentBookingModal.vue';
import EmergencyFloatingBar from '../components/EmergencyFloatingBar.vue';

const isBookingOpen = ref(false);
const bookingData = ref(null);

const handleOpenBooking = (data) => {
  bookingData.value = data || null;
  isBookingOpen.value = true;
};
</script>

<template>
  <div class="space-y-0">
    <HeroSection @open-booking="handleOpenBooking" />
    <FeaturesSection />
    <DepartmentsSection @open-booking="handleOpenBooking" />
    <DoctorsSection @open-booking="handleOpenBooking" />
    <ServicesSection @open-booking="handleOpenBooking" />
    <EventsSection @open-booking="handleOpenBooking" />
    <GallerySection />
    <TestimonialsSection />

    <!-- Appointment Booking Modal -->
    <AppointmentBookingModal 
      :is-open="isBookingOpen" 
      :initial-data="bookingData"
      @close="isBookingOpen = false"
    />

    <!-- Floating Quick Actions -->
    <EmergencyFloatingBar @open-booking="handleOpenBooking" />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useHospitalStore } from '../stores/hospitalStore';
import { useRouter } from 'vue-router';
import {
  Calendar, Star, Award, Clock, ArrowRight,
  Stethoscope, ShieldCheck, MapPin
} from 'lucide-vue-next';

const emit = defineEmits(['open-booking']);
const router = useRouter();
const hospitalStore = useHospitalStore();

const activeFilter = ref('all');

const handleBookDoctor = (doc) => {
  emit('open-booking', {
    doctor_id: doc.id,
    department_id: doc.doctor_profile?.department_id,
  });
};

const getAvatarUrl = (avatar) => {
  if (!avatar) return '/assets/images/doc1.jpg';

  if (avatar.startsWith('http') || avatar.startsWith('data:')) {
    return avatar;
  }

  if (avatar.startsWith('/')) {
    return avatar;
  }

  return '/' + avatar;
};

const onDoctorImgError = (e, avatar) => {
  const currentSrc = e.target.src;

  if (avatar && !currentSrc.startsWith('data:')) {
    const cleanPath = avatar.startsWith('/')
      ? avatar.substring(1)
      : avatar;

    e.target.src = `/${cleanPath}`;
    return;
  }

  e.target.src = '/assets/images/doc1.jpg';
};
</script>

<template>
  <section class="py-20 bg-slate-900 text-white relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

      <!-- Section Title -->
      <div class="text-center max-w-3xl mx-auto mb-16">
        <span
          class="text-xs font-bold uppercase tracking-wider text-brand-400 bg-brand-500/20 px-3 py-1 rounded-full border border-brand-500/30"
        >
          World-Class Medical Faculty
        </span>

        <h2 class="text-3xl sm:text-4xl font-extrabold text-white mt-3 tracking-tight">
          Meet Our Senior Consultant Physicians
        </h2>

        <p class="mt-3 text-base text-slate-300">
          Board-certified specialists with decades of combined clinical leadership
          in surgery, critical care, and precision medicine.
        </p>
      </div>

      <!-- Doctors Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div
          v-for="doc in hospitalStore.doctors"
          :key="doc.id"
          class="bg-slate-800/80 backdrop-blur-md rounded-3xl border border-slate-700/60 overflow-hidden shadow-xl hover:border-brand-500/50 hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group"
        >

          <!-- Doctor Image -->
          <div class="relative h-64 overflow-hidden bg-slate-950">
            <img
              :src="getAvatarUrl(doc.avatar)"
              :alt="doc.name"
              @error="onDoctorImgError($event, doc.avatar)"
              class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500 bg-slate-900"
            />

            <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent"></div>

            <div
              class="absolute top-3 right-3 bg-brand-600/90 text-white text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider backdrop-blur-md"
            >
              {{ doc.doctor_profile?.experience_years }}+ Yrs Exp
            </div>

            <div class="absolute bottom-3 left-3 right-3">
              <span class="text-xs font-semibold text-brand-300 block">
                {{ doc.doctor_profile?.department?.name || 'Specialist' }}
              </span>

              <h3 class="text-lg font-bold text-white leading-tight">
                {{ doc.name }}
              </h3>
            </div>
          </div>

          <!-- Card Details -->
          <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
            <div class="space-y-2">

              <p class="text-xs font-medium text-slate-300">
                {{ doc.doctor_profile?.qualification }}
              </p>

              <div class="flex items-center gap-1.5 text-xs text-slate-400">
                <MapPin class="w-3.5 h-3.5 text-brand-400 flex-shrink-0" />
                <span>{{ doc.doctor_profile?.room_no }}</span>
              </div>

              <div class="flex items-center gap-1.5 text-xs text-slate-400">
                <Clock class="w-3.5 h-3.5 text-emerald-400 flex-shrink-0" />
                <span>
                  {{ doc.doctor_profile?.available_days }} (09:00 - 17:00)
                </span>
              </div>

            </div>

            <div class="pt-4 border-t border-slate-700/60 flex items-center justify-between">
              <div>
                <span class="text-[11px] text-slate-400 block">
                  Consultation
                </span>

                <span class="text-base font-extrabold text-white">
                  ${{ doc.doctor_profile?.consultation_fee }}
                </span>
              </div>

              <button
                @click="handleBookDoctor(doc)"
                class="flex items-center gap-1.5 bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold py-2.5 px-4 rounded-xl shadow-md transition-all"
              >
                <Calendar class="w-3.5 h-3.5" />
                Book Now
              </button>
            </div>

          </div>
        </div>
      </div>

    </div>
  </section>
</template>
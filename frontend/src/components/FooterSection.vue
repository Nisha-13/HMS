<script setup>
import { 
  HeartPulse, Phone, Mail, MapPin, Clock, 
  ShieldCheck, ArrowRight, Activity, Tv
} from 'lucide-vue-next';

const emit = defineEmits(['open-booking']);
</script>

<template>
  <footer class="bg-slate-950 text-white border-t border-slate-800 pt-16 pb-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 pb-12 border-b border-slate-800">
        
        <!-- Hospital Branding & Bio -->
        <div class="lg:col-span-2 space-y-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-brand-600 flex items-center justify-center text-white shadow-lg">
              <HeartPulse class="w-6 h-6" />
            </div>
            <div>
              <span class="text-xl font-extrabold text-white">HealthPulse Hospital</span>
              <p class="text-[10px] text-brand-400 font-bold uppercase tracking-wider">Smart Medical Center</p>
            </div>
          </div>

          <p class="text-xs text-slate-400 leading-relaxed pr-6">
            HealthPulse Hospital is a multidisciplinary quaternary-care institution committed to world-class clinical outcomes, advanced robotic surgery, and compassionate patient rehabilitation.
          </p>

          <div class="flex items-center gap-3 pt-2">
            <button 
              @click="$emit('open-booking')"
              class="bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-all">
              Book Appointment
            </button>
            <router-link 
              to="/queue-display" 
              target="_blank"
              class="flex items-center gap-1 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold px-3.5 py-2.5 rounded-xl border border-slate-700">
              <Tv class="w-3.5 h-3.5 text-emerald-400" />
              Live Queue Board
            </router-link>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="space-y-3">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300">Quick Links</h4>
          <ul class="space-y-2 text-xs text-slate-400">
            <li><router-link to="/" class="hover:text-white transition-colors">Home Page</router-link></li>
            <li><router-link to="/doctors" class="hover:text-white transition-colors">Doctors Directory</router-link></li>
            <li><router-link to="/departments" class="hover:text-white transition-colors">Clinical Departments</router-link></li>
            <li><router-link to="/services" class="hover:text-white transition-colors">Hospital Services</router-link></li>
            <li><router-link to="/events" class="hover:text-white transition-colors">Health Camps & Events</router-link></li>
            <li><router-link to="/gallery" class="hover:text-white transition-colors">Facility Gallery</router-link></li>
          </ul>
        </div>

        <!-- Clinical Faculties -->
        <div class="space-y-3">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300">Faculties</h4>
          <ul class="space-y-2 text-xs text-slate-400">
            <li><router-link to="/doctors?department_id=1" class="hover:text-white transition-colors">Cardiology & Cath Lab</router-link></li>
            <li><router-link to="/doctors?department_id=2" class="hover:text-white transition-colors">Neurology & Stroke Unit</router-link></li>
            <li><router-link to="/doctors?department_id=3" class="hover:text-white transition-colors">Pediatrics & Neonatal</router-link></li>
            <li><router-link to="/doctors?department_id=4" class="hover:text-white transition-colors">Trauma & Orthopedics</router-link></li>
            <li><router-link to="/doctors?department_id=5" class="hover:text-white transition-colors">Dental & Orthodontics</router-link></li>
            <li><router-link to="/doctors?department_id=7" class="hover:text-white transition-colors">3-Tesla MRI Diagnostics</router-link></li>
          </ul>
        </div>

        <!-- Emergency & Contact Info -->
        <div class="space-y-3">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300">Contact & Support</h4>
          <div class="space-y-2.5 text-xs text-slate-400">
            <div class="flex items-start gap-2">
              <Phone class="w-4 h-4 text-red-400 flex-shrink-0 mt-0.5" />
              <div>
                <span class="text-white font-bold block">+1 (800) 456-7890</span>
                <span class="text-[10px] text-slate-500">24/7 Level-1 Emergency</span>
              </div>
            </div>

            <div class="flex items-start gap-2">
              <Mail class="w-4 h-4 text-brand-400 flex-shrink-0 mt-0.5" />
              <span>info@healthpulse.com</span>
            </div>

            <div class="flex items-start gap-2">
              <MapPin class="w-4 h-4 text-brand-400 flex-shrink-0 mt-0.5" />
              <span>450 Wellness Blvd, Medical Center, NY 10001</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Copyright & Compliance Bar -->
      <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
        <p>© 2026 HealthPulse Hospital & Research Center. All Rights Reserved.</p>
        <div class="flex items-center space-x-6">
          <span class="flex items-center gap-1 text-slate-400">
            <ShieldCheck class="w-4 h-4 text-emerald-400" />
            JCI & HIPAA Certified
          </span>
          <router-link to="/privacy" class="hover:text-brand-300 text-slate-400 underline transition-colors">
            Privacy Policy
          </router-link>
          <router-link to="/contact" class="hover:text-white transition-colors">
            Patient Support
          </router-link>
        </div>
      </div>
    </div>
  </footer>
</template>

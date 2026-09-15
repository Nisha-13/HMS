<script setup>
import { ref } from 'vue';
import { useHospitalStore } from '../stores/hospitalStore';
import { 
  Phone, Mail, MapPin, Clock, Send, 
  CheckCircle2, AlertCircle, ShieldAlert 
} from 'lucide-vue-next';

const hospitalStore = useHospitalStore();

const form = ref({
  name: '',
  email: '',
  phone: '',
  department_name: 'General Inquiries',
  message: '',
});

const loading = ref(false);
const successMsg = ref('');

const handleSubmit = async () => {
  loading.value = true;
  successMsg.value = '';
  try {
    const res = await hospitalStore.sendContactInquiry(form.value);
    if (res.success) {
      successMsg.value = res.message;
      form.value = {
        name: '',
        email: '',
        phone: '',
        department_name: 'General Inquiries',
        message: '',
      };
    }
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="py-16 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- Page Header -->
      <div class="text-center max-w-3xl mx-auto mb-16">
        <span class="text-xs font-bold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1.5 rounded-full border border-brand-200">
          Get In Touch
        </span>
        <h1 class="text-3xl sm:text-5xl font-extrabold text-slate-900 mt-4 tracking-tight">
          Contact HealthPulse Hospital
        </h1>
        <p class="text-base text-slate-600 mt-3">
          Our clinical coordination desks, emergency teams, and patient relations staff are available 24/7.
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        
        <!-- Left: Contact Details & Emergency Cards -->
        <div class="lg:col-span-5 space-y-6">
          
          <!-- 24/7 Emergency Box -->
          <div class="bg-gradient-to-br from-red-600 to-rose-700 text-white rounded-3xl p-8 shadow-xl relative overflow-hidden">
            <div class="flex items-center gap-3 mb-4">
              <div class="p-3 bg-white/20 rounded-2xl backdrop-blur-md">
                <ShieldAlert class="w-7 h-7 text-white animate-pulse" />
              </div>
              <div>
                <span class="text-xs uppercase font-bold tracking-wider text-red-200">Immediate Triage</span>
                <h3 class="text-xl font-extrabold">24/7 Trauma Emergency</h3>
              </div>
            </div>
            <p class="text-xs text-red-100 mb-6 leading-relaxed">
              For acute cardiac emergencies, stroke symptoms, major trauma, or pediatric distress, call our priority hotline immediately:
            </p>
            <div class="bg-white/15 backdrop-blur-md rounded-2xl p-4 flex items-center justify-between border border-white/20">
              <span class="text-lg sm:text-2xl font-extrabold tracking-wide">+1 (800) 456-7890</span>
              <span class="text-xs font-bold bg-white text-red-700 px-3 py-1 rounded-full uppercase">Call Now</span>
            </div>
          </div>

          <!-- Info Cards -->
          <div class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm space-y-6">
            <div class="flex items-start gap-4">
              <div class="p-3 rounded-2xl bg-brand-50 text-brand-600">
                <MapPin class="w-6 h-6" />
              </div>
              <div>
                <h4 class="text-sm font-bold text-slate-900">Hospital Location</h4>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                  HealthPulse Hospital, 450 Wellness Blvd, Medical Center District, NY 10001
                </p>
              </div>
            </div>

            <div class="flex items-start gap-4">
              <div class="p-3 rounded-2xl bg-brand-50 text-brand-600">
                <Clock class="w-6 h-6" />
              </div>
              <div>
                <h4 class="text-sm font-bold text-slate-900">Visiting & OPD Hours</h4>
                <p class="text-xs text-slate-600 mt-1">
                  OPD Clinics: Mon – Sat (08:00 AM – 08:00 PM)<br />
                  Emergency & Inpatient ICU: 24 Hours / 7 Days
                </p>
              </div>
            </div>

            <div class="flex items-start gap-4">
              <div class="p-3 rounded-2xl bg-brand-50 text-brand-600">
                <Mail class="w-6 h-6" />
              </div>
              <div>
                <h4 class="text-sm font-bold text-slate-900">Email & Inquiries</h4>
                <p class="text-xs text-slate-600 mt-1">
                  General: info@healthpulse.com<br />
                  Appointments: appointments@healthpulse.com
                </p>
              </div>
            </div>
          </div>

        </div>

        <!-- Right: Interactive Contact Form -->
        <div class="lg:col-span-7">
          <div class="bg-white rounded-3xl p-8 sm:p-10 border border-slate-100 shadow-sm">
            <h3 class="text-2xl font-bold text-slate-900 mb-2">Send Us A Message</h3>
            <p class="text-xs text-slate-500 mb-8">
              Fill in your inquiry details below. Our medical coordinator will respond within 2 hours.
            </p>

            <div v-if="successMsg" class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs flex items-center gap-2">
              <CheckCircle2 class="w-5 h-5 flex-shrink-0 text-emerald-600" />
              <span>{{ successMsg }}</span>
            </div>

            <form @submit.prevent="handleSubmit" class="space-y-5">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    Your Name <span class="text-red-500">*</span>
                  </label>
                  <input 
                    type="text" 
                    v-model="form.name"
                    required
                    placeholder="e.g. Eleanor Vance"
                    class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white"
                  />
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    Email Address <span class="text-red-500">*</span>
                  </label>
                  <input 
                    type="email" 
                    v-model="form.email"
                    required
                    placeholder="email@example.com"
                    class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white"
                  />
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    Phone Number
                  </label>
                  <input 
                    type="tel" 
                    v-model="form.phone"
                    placeholder="+1 (555) 000-0000"
                    class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white"
                  />
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    Department / Topic
                  </label>
                  <select 
                    v-model="form.department_name"
                    class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
                    <option>General Inquiries</option>
                    <option>Billing & Insurance</option>
                    <option>Medical Records</option>
                    <option v-for="dept in hospitalStore.departments" :key="dept.id" :value="dept.name">
                      {{ dept.name }}
                    </option>
                  </select>
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                  Message / Details <span class="text-red-500">*</span>
                </label>
                <textarea 
                  v-model="form.message"
                  required
                  rows="4"
                  placeholder="How can our clinical team help you today?..."
                  class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white"
                ></textarea>
              </div>

              <button 
                type="submit" 
                :disabled="loading"
                class="flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs uppercase tracking-wider py-4 px-8 rounded-2xl shadow-lg shadow-brand-500/25 transition-all disabled:opacity-50">
                <Send class="w-4 h-4" />
                <span v-if="loading">Sending Message...</span>
                <span v-else>Send Inquiry Message</span>
              </button>
            </form>
          </div>
        </div>

      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../services/api';
import { useAuthStore } from '../stores/authStore';
import AppointmentBookingModal from '../components/AppointmentBookingModal.vue';
import {
  Calendar, FileText, DollarSign, Clock, User,
  Download, Printer, CheckCircle2, Ticket, Sparkles, Plus,
  Camera, AlertCircle, Phone, Mail, Droplets, Shield
} from 'lucide-vue-next';

const authStore = useAuthStore();
const appointments = ref([]);
const prescriptions = ref([]);
const invoices = ref([]);
const campPasses = ref([]);
const loading = ref(true);
const activeTab = ref('appointments'); // appointments, prescriptions, billing, camps
const isBookingOpen = ref(false);
const updatingAvatar = ref(false);

const getAvatarUrl = (avatar) => {
  if (!avatar) return '/assets/images/default-patient.svg';
  if (avatar.startsWith('http') || avatar.startsWith('data:')) return avatar;
  if (avatar.startsWith('/')) return avatar;
  return '/' + avatar;
};

const onAvatarError = (e) => {
  const currentSrc = e.target.src;
  const avatarPath = authStore.user?.avatar;

  if (avatarPath && !currentSrc.startsWith('data:')) {
    const cleanPath = avatarPath.startsWith('/')
      ? avatarPath.substring(1)
      : avatarPath;

    e.target.src = `/${cleanPath}`;
    return;
  }

  e.target.src = '/assets/images/default-patient.svg';
};

const handleAvatarUpload = async (e) => {
  const file = e.target.files[0];
  if (!file) return;

  if (file.size > 5 * 1024 * 1024) {
    alert('Please select an image smaller than 5MB.');
    e.target.value = '';
    return;
  }

  const reader = new FileReader();
  reader.onload = async (event) => {
    updatingAvatar.value = true;
    try {
      const res = await authStore.updateAvatar(event.target.result);
      if (!res.success) {
        alert(res.message || 'Failed to update profile picture.');
      }
    } catch (err) {
      console.error(err);
      alert('An error occurred while uploading profile picture.');
    } finally {
      updatingAvatar.value = false;
      e.target.value = '';
    }
  };
  reader.readAsDataURL(file);
};

const fetchPatientData = async () => {
  loading.value = true;
  try {
    const [resApt, resRx, resInv, resCamp] = await Promise.all([
      api.get('/appointments'),
      api.get('/prescriptions'),
      api.get('/invoices'),
      api.get('/patient/event-registrations').catch(() => ({
        data: { success: false, registrations: [] }
      })),
    ]);

    if (resApt.data.success) appointments.value = resApt.data.appointments;
    if (resRx.data.success) prescriptions.value = resRx.data.prescriptions;
    if (resInv.data.success) invoices.value = resInv.data.invoices;
    if (resCamp.data?.success) campPasses.value = resCamp.data.registrations;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const handlePrint = () => {
  window.print();
};

onMounted(() => {
  fetchPatientData();
});
</script>

<template>
  <div class="min-h-screen bg-slate-100 py-6 sm:py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
      
      <!-- Patient Profile Header (Fully Responsive) -->
      <div class="bg-white rounded-3xl p-5 sm:p-8 border border-slate-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6 transition-all">
        
        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-6 text-center sm:text-left">
          <!-- Avatar with Instant Change Button -->
          <div class="relative group">
            <img 
              :src="getAvatarUrl(authStore.user?.avatar)" 
              @error="onAvatarError"
              class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl sm:rounded-3xl object-cover border-2 border-brand-500 shadow-md bg-white transition-transform group-hover:scale-102"
              alt="Patient Profile Picture"
            />
            <label 
              title="Change Profile Picture"
              class="absolute -bottom-1.5 -right-1.5 p-1.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl shadow-lg cursor-pointer transition-transform hover:scale-110">
              <Camera class="w-3.5 h-3.5" :class="{ 'animate-spin': updatingAvatar }" />
              <input type="file" accept="image/*" class="hidden" @change="handleAvatarUpload" />
            </label>
          </div>

          <!-- Patient Identity Details -->
          <div class="space-y-1.5">
            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
              <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                {{ authStore.user?.name || 'Valued Patient' }}
              </h1>
              <span class="text-[11px] font-bold text-brand-700 bg-brand-50 border border-brand-200 px-2.5 py-0.5 rounded-full">
                {{ authStore.user?.patient_profile?.mrn || 'MRN-2026-001' }}
              </span>
            </div>

            <!-- Contact Line -->
            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3 text-xs text-slate-500">
              <span class="flex items-center gap-1">
                <Mail class="w-3.5 h-3.5 text-slate-400" />
                {{ authStore.user?.email }}
              </span>
              <span v-if="authStore.user?.phone" class="flex items-center gap-1">
                <Phone class="w-3.5 h-3.5 text-slate-400" />
                {{ authStore.user?.phone }}
              </span>
            </div>

            <!-- Clinical Badges -->
            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 pt-1">
              <span class="inline-flex items-center gap-1 text-[11px] font-semibold bg-red-50 text-red-700 border border-red-100 px-2.5 py-1 rounded-lg">
                <Droplets class="w-3 h-3 text-red-500" />
                Blood Group: <strong>{{ authStore.user?.patient_profile?.blood_group || 'O+' }}</strong>
              </span>
              <span class="inline-flex items-center gap-1 text-[11px] font-semibold bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg">
                <Shield class="w-3 h-3 text-slate-500" />
                Allergies: <strong>{{ authStore.user?.patient_profile?.allergies || 'None' }}</strong>
              </span>
            </div>
          </div>
        </div>

        <!-- Call to Action Button -->
        <div class="w-full md:w-auto">
          <button 
            @click="isBookingOpen = true"
            class="w-full md:w-auto flex items-center justify-center gap-2 bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white font-bold text-xs uppercase tracking-wider py-3.5 px-6 rounded-xl shadow-lg shadow-brand-500/25 hover:shadow-brand-500/40 transition-all cursor-pointer">
            <Plus class="w-4 h-4" />
            <span>Book Consultation</span>
          </button>
        </div>

      </div>

      <!-- Navigation Tabs Bar (Scrollable on Mobile) -->
      <div class="overflow-x-auto pb-1 max-w-full">
        <div class="flex items-center space-x-2 border-b border-slate-200 min-w-max">
          <button 
            @click="activeTab = 'appointments'"
            class="px-4 sm:px-5 py-3 text-xs font-bold uppercase tracking-wider border-b-2 transition-all flex items-center gap-2 cursor-pointer"
            :class="activeTab === 'appointments' ? 'border-brand-600 text-brand-600 bg-white rounded-t-2xl shadow-sm' : 'border-transparent text-slate-500 hover:text-slate-800'">
            <Calendar class="w-4 h-4" />
            <span>Appointments</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="activeTab === 'appointments' ? 'bg-brand-100 text-brand-700' : 'bg-slate-200 text-slate-600'">
              {{ appointments.length }}
            </span>
          </button>

          <button 
            @click="activeTab = 'prescriptions'"
            class="px-4 sm:px-5 py-3 text-xs font-bold uppercase tracking-wider border-b-2 transition-all flex items-center gap-2 cursor-pointer"
            :class="activeTab === 'prescriptions' ? 'border-brand-600 text-brand-600 bg-white rounded-t-2xl shadow-sm' : 'border-transparent text-slate-500 hover:text-slate-800'">
            <FileText class="w-4 h-4" />
            <span>Prescriptions & EMR</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="activeTab === 'prescriptions' ? 'bg-brand-100 text-brand-700' : 'bg-slate-200 text-slate-600'">
              {{ prescriptions.length }}
            </span>
          </button>

          <button 
            @click="activeTab = 'billing'"
            class="px-4 sm:px-5 py-3 text-xs font-bold uppercase tracking-wider border-b-2 transition-all flex items-center gap-2 cursor-pointer"
            :class="activeTab === 'billing' ? 'border-brand-600 text-brand-600 bg-white rounded-t-2xl shadow-sm' : 'border-transparent text-slate-500 hover:text-slate-800'">
            <DollarSign class="w-4 h-4" />
            <span>Invoices & Billing</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="activeTab === 'billing' ? 'bg-brand-100 text-brand-700' : 'bg-slate-200 text-slate-600'">
              {{ invoices.length }}
            </span>
          </button>

          <button 
            @click="activeTab = 'camps'"
            class="px-4 sm:px-5 py-3 text-xs font-bold uppercase tracking-wider border-b-2 transition-all flex items-center gap-2 cursor-pointer"
            :class="activeTab === 'camps' ? 'border-brand-600 text-brand-600 bg-white rounded-t-2xl shadow-sm' : 'border-transparent text-slate-500 hover:text-slate-800'">
            <Ticket class="w-4 h-4" />
            <span>Free Camp Passes</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="activeTab === 'camps' ? 'bg-teal-100 text-teal-700' : 'bg-slate-200 text-slate-600'">
              {{ campPasses.length }}
            </span>
          </button>
        </div>
      </div>

      <!-- Tab 1: Appointments -->
      <div v-show="activeTab === 'appointments'" class="space-y-4">
        <div v-if="appointments.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
          <div 
            v-for="apt in appointments" 
            :key="apt.id"
            class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-100 shadow-sm space-y-4 flex flex-col justify-between hover:shadow-md transition">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <span class="text-xs font-mono font-bold text-slate-500">{{ apt.appointment_no }}</span>
              <span 
                class="text-[10px] font-bold uppercase px-2.5 py-1 rounded-full"
                :class="{
                  'bg-emerald-100 text-emerald-700': apt.status === 'completed',
                  'bg-amber-100 text-amber-700 animate-pulse': apt.status === 'in_consultation',
                  'bg-blue-100 text-blue-700': apt.status === 'confirmed',
                  'bg-red-100 text-red-700': apt.status === 'cancelled',
                }">
                {{ apt.status }}
              </span>
            </div>

            <!-- OPD Token Box -->
            <div 
              class="p-4 rounded-2xl border flex items-center justify-between"
              :class="apt.payment_status === 'paid' 
                ? 'bg-brand-50/80 border-brand-100' 
                : 'bg-amber-50/80 border-amber-200'">
              <div>
                <span class="text-[10px] font-bold uppercase tracking-wider block"
                  :class="apt.payment_status === 'paid' ? 'text-brand-600' : 'text-amber-600'">
                  {{ apt.payment_status === 'paid' ? 'OPD Token' : '⚠ Payment Pending' }}
                </span>
                <span class="text-2xl font-black font-mono"
                  :class="apt.payment_status === 'paid' ? 'text-brand-900' : 'text-amber-800'">
                  {{ apt.token_no || 'T-001' }}
                </span>
              </div>
              <div class="text-right">
                <span class="text-xs text-slate-500 block">{{ apt.payment_status === 'paid' ? 'Consulting Room' : 'Action Required' }}</span>
                <span v-if="apt.payment_status === 'paid'" class="text-xs font-bold text-slate-800">{{ apt.doctor?.doctor_profile?.room_no || 'Room 101' }}</span>
                <span v-else class="text-[11px] font-bold text-amber-700 leading-tight">Pay at Reception<br/>to enter queue</span>
              </div>
            </div>

            <!-- Details -->
            <div class="space-y-1.5 text-xs text-slate-600">
              <p><strong>Doctor:</strong> {{ apt.doctor?.name }} ({{ apt.department?.name || 'Specialist' }})</p>
              <p><strong>Schedule:</strong> {{ apt.appointment_date }} at {{ apt.time_slot }}</p>
              <p v-if="apt.symptoms" class="line-clamp-2"><strong>Symptoms:</strong> {{ apt.symptoms }}</p>
            </div>

            <!-- Footer -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
              <span class="text-slate-500">Fee: <strong class="text-slate-900">${{ apt.fee }}</strong></span>
              <span 
                v-if="apt.payment_status === 'paid'"
                class="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                ✓ Paid
              </span>
              <span 
                v-else
                class="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 flex items-center gap-1 animate-pulse">
                💰 Pay at Reception
              </span>
            </div>

          </div>
        </div>

        <div v-else class="py-16 text-center bg-white rounded-3xl p-8 border border-slate-100">
          <Calendar class="w-12 h-12 text-slate-300 mx-auto mb-3" />
          <h3 class="text-base font-bold text-slate-700">No Appointments Found</h3>
          <p class="text-xs text-slate-500 mt-1">Book your doctor consultation to get an OPD queue token.</p>
          <button 
            @click="isBookingOpen = true"
            class="mt-4 px-4 py-2 bg-brand-600 text-white rounded-xl text-xs font-bold shadow hover:bg-brand-700 transition">
            Book Appointment Now
          </button>
        </div>
      </div>

      <!-- Tab 2: Prescriptions -->
      <div v-show="activeTab === 'prescriptions'" class="space-y-4">
        <div v-if="prescriptions.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
          <div 
            v-for="rx in prescriptions" 
            :key="rx.id"
            class="bg-white rounded-3xl p-5 sm:p-7 border border-slate-100 shadow-sm space-y-4 hover:shadow-md transition">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <div>
                <span class="text-xs font-mono font-bold text-brand-700 block">{{ rx.prescription_no }}</span>
                <h3 class="text-base font-bold text-slate-900 mt-0.5">{{ rx.diagnosis }}</h3>
              </div>
              <span class="text-xs text-slate-400">{{ new Date(rx.created_at).toLocaleDateString() }}</span>
            </div>

            <!-- Vitals -->
            <div class="flex flex-wrap items-center gap-3 text-xs bg-slate-50 p-3 rounded-xl border border-slate-100">
              <span>BP: <strong>{{ rx.bp || '120/80' }}</strong></span>
              <span>Pulse: <strong>{{ rx.pulse || '72 bpm' }}</strong></span>
              <span>Temp: <strong>{{ rx.temperature || '98.6 F' }}</strong></span>
            </div>

            <!-- Medicines Table -->
            <div class="space-y-2">
              <h4 class="text-xs font-bold uppercase text-slate-400">Prescribed Regimen</h4>
              <div class="space-y-2">
                <div 
                  v-for="item in rx.items" 
                  :key="item.id"
                  class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-xs">
                  <div>
                    <strong class="text-slate-900">{{ item.medicine_name }}</strong>
                    <span class="text-slate-500 block">{{ item.dosage }} • {{ item.frequency }} • {{ item.duration }}</span>
                  </div>
                  <span class="text-[11px] font-semibold text-brand-600 bg-brand-50 px-2 py-0.5 rounded self-start sm:self-center">
                    {{ item.instruction }}
                  </span>
                </div>
              </div>
            </div>

            <p v-if="rx.advice_notes" class="text-xs text-slate-600 bg-amber-50/60 p-3 rounded-xl border border-amber-100">
              <strong>Doctor Advice:</strong> {{ rx.advice_notes }}
            </p>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
              <span>Prescribed by <strong>{{ rx.doctor?.name }}</strong></span>
              <button @click="handlePrint" class="flex items-center gap-1 text-brand-600 font-bold hover:underline cursor-pointer">
                <Printer class="w-3.5 h-3.5" />
                <span>Print PDF</span>
              </button>
            </div>

          </div>
        </div>

        <div v-else class="py-16 text-center bg-white rounded-3xl p-8 border border-slate-100">
          <FileText class="w-12 h-12 text-slate-300 mx-auto mb-3" />
          <h3 class="text-base font-bold text-slate-700">No Prescriptions Found</h3>
          <p class="text-xs text-slate-500 mt-1">Prescriptions issued by your physician will appear here.</p>
        </div>
      </div>

      <!-- Tab 3: Billing & Invoices (Fully Responsive Cards on Mobile + Table on Desktop) -->
      <div v-show="activeTab === 'billing'" class="space-y-4">
        <div class="bg-white rounded-3xl p-5 sm:p-8 border border-slate-100 shadow-sm space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
              <h3 class="text-base font-bold text-slate-900">Patient Invoices & Statements</h3>
              <p class="text-xs text-slate-400">Track all consultation and hospital billing receipts</p>
            </div>
            <span class="text-xs font-bold text-brand-600 bg-brand-50 px-3 py-1 rounded-full">
              {{ invoices.length }} Total Invoices
            </span>
          </div>
          
          <div v-if="invoices.length > 0">
            <!-- Mobile Cards View (sm and smaller) -->
            <div class="grid grid-cols-1 gap-3 md:hidden">
              <div 
                v-for="inv in invoices" 
                :key="'mob-' + inv.id"
                class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2.5">
                <div class="flex items-center justify-between">
                  <span class="font-mono font-bold text-xs text-brand-700">{{ inv.invoice_no }}</span>
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700">
                    {{ inv.payment_status }}
                  </span>
                </div>
                <div class="flex items-center justify-between text-xs">
                  <span class="text-slate-500">Date: {{ new Date(inv.created_at).toLocaleDateString() }}</span>
                  <span class="text-sm font-black text-slate-900">${{ inv.total_amount }}</span>
                </div>
                <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-[11px] text-slate-500">
                  <span>Method: <strong class="uppercase text-slate-700">{{ inv.payment_method }}</strong></span>
                  <span class="text-emerald-600 font-semibold">Receipt Generated ✓</span>
                </div>
              </div>
            </div>

            <!-- Desktop Table View (md and larger) -->
            <div class="hidden md:block overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="text-slate-400 border-b border-slate-100 pb-2">
                    <th class="pb-3 font-bold uppercase tracking-wider">Invoice No</th>
                    <th class="pb-3 font-bold uppercase tracking-wider">Date</th>
                    <th class="pb-3 font-bold uppercase tracking-wider">Total Amount</th>
                    <th class="pb-3 font-bold uppercase tracking-wider">Payment Status</th>
                    <th class="pb-3 font-bold uppercase tracking-wider">Method</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-slate-50/70 transition-colors">
                    <td class="py-3 font-mono font-bold text-brand-700">{{ inv.invoice_no }}</td>
                    <td class="py-3 text-slate-600">{{ new Date(inv.created_at).toLocaleDateString() }}</td>
                    <td class="py-3 font-bold text-slate-900">${{ inv.total_amount }}</td>
                    <td class="py-3">
                      <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700">
                        {{ inv.payment_status }}
                      </span>
                    </td>
                    <td class="py-3 uppercase text-slate-500 font-semibold">{{ inv.payment_method }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-else class="py-12 text-center">
            <DollarSign class="w-10 h-10 text-slate-300 mx-auto mb-2" />
            <p class="text-xs text-slate-500">No invoices generated yet.</p>
          </div>
        </div>
      </div>

      <!-- Tab 4: Free Camp Passes -->
      <div v-show="activeTab === 'camps'" class="space-y-4">
        <div v-if="campPasses.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
          <div 
            v-for="camp in campPasses" 
            :key="camp.id"
            class="bg-white rounded-3xl p-5 sm:p-6 border border-teal-100 shadow-sm space-y-4 flex flex-col justify-between hover:shadow-md transition">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <span class="text-xs font-mono font-black text-teal-800 bg-teal-50 px-2.5 py-1 rounded-lg border border-teal-200">
                #{{ camp.pass_code }}
              </span>
              <span class="text-[10px] font-bold uppercase px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 flex items-center gap-1">
                <CheckCircle2 class="w-3 h-3" /> {{ camp.status || 'Confirmed' }}
              </span>
            </div>

            <div class="space-y-2">
              <h4 class="font-bold text-sm text-slate-900 leading-snug">{{ camp.event?.title || 'Community Health Camp' }}</h4>
              <div class="space-y-1 text-xs text-slate-500">
                <div class="flex items-center gap-1.5">
                  <Calendar class="w-3.5 h-3.5 text-teal-600 shrink-0" />
                  <span>{{ camp.event?.event_date }} • {{ camp.event?.time_slot }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <Clock class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                  <span>Category: {{ camp.health_concern }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <User class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                  <span>Attendee: <strong>{{ camp.attendee_name }}</strong> ({{ camp.attendees_count }} Person)</span>
                </div>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
              <span class="text-[11px] font-black text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg">
                100% FREE ($0)
              </span>
              <span class="text-xs text-teal-700 font-bold">
                Pass Verified ✓
              </span>
            </div>
          </div>
        </div>

        <div v-else class="bg-white rounded-3xl p-10 border border-slate-100 text-center space-y-3">
          <Ticket class="w-12 h-12 text-slate-300 mx-auto" />
          <h4 class="font-bold text-slate-700 text-sm">No Free Camp Registrations Yet</h4>
          <p class="text-xs text-slate-400 max-w-sm mx-auto">
            You haven't registered for any community medical camps yet. Browse our free camps and claim your entry pass.
          </p>
          <router-link to="/events" class="inline-flex items-center gap-1 text-xs font-bold text-teal-700 hover:underline pt-2">
            Browse Free Health Camps →
          </router-link>
        </div>
      </div>

    </div>

    <!-- Booking Modal -->
    <AppointmentBookingModal 
      :is-open="isBookingOpen" 
      @close="isBookingOpen = false"
      @booked="fetchPatientData"
    />
  </div>
</template>

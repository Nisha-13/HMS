<script setup>
import { ref, watch, computed } from 'vue';
import api from '../services/api';
import { jsPDF } from 'jspdf';
import { useAuthStore } from '../stores/authStore';
import { 
  X, Calendar, Clock, MapPin, Users, Ticket, 
  CheckCircle2, AlertCircle, Sparkles, Download, ArrowRight,
  ShieldCheck, User, CreditCard, HeartPulse, FileText, Check,
  UserCheck, LogIn
} from 'lucide-vue-next';

const props = defineProps({
  isOpen: Boolean,
  event: Object,
});

const emit = defineEmits(['close']);

const authStore = useAuthStore();

const step = ref(1); // 1: Form, 2: Confirmed Pass
const loading = ref(false);
const errorMsg = ref('');
const passResult = ref(null);
const isDownloaded = ref(false);

const getPrefilledData = () => {
  const user = authStore.user;
  return {
    attendee_name: user?.name || '',
    attendee_phone: user?.phone || '',
    cnic_id: user?.patient_profile?.cnic || user?.cnic || '',
    age_group: 'Adult (18 - 49 Years)',
    health_concern: 'Cardiac & Blood Pressure Checkup',
    attendee_email: user?.email || '',
    attendees_count: 1,
    declaration: true,
  };
};

const form = ref(getPrefilledData());

watch(() => props.isOpen, (open) => {
  if (open) {
    step.value = 1;
    errorMsg.value = '';
    passResult.value = null;
    isDownloaded.value = false;
    form.value = getPrefilledData();
  }
});

// Auto-mask CNIC: XXXXX-XXXXXXX-X
const handleCnicInput = (e) => {
  let digits = e.target.value.replace(/\D/g, '').slice(0, 13);
  let formatted = digits;
  if (digits.length > 5 && digits.length <= 12) {
    formatted = `${digits.slice(0, 5)}-${digits.slice(5)}`;
  } else if (digits.length > 12) {
    formatted = `${digits.slice(0, 5)}-${digits.slice(5, 12)}-${digits.slice(12, 13)}`;
  }
  form.value.cnic_id = formatted;
};

// Auto-format Pakistani mobile number: 03XX-XXXXXXX
const handlePhoneInput = (e) => {
  let digits = e.target.value.replace(/\D/g, '').slice(0, 11);
  let formatted = digits;
  if (digits.length > 4) {
    formatted = `${digits.slice(0, 4)}-${digits.slice(4)}`;
  }
  form.value.attendee_phone = formatted;
};

const handleSubmit = async () => {
  if (!props.event) return;
  if (!form.value.declaration) {
    errorMsg.value = 'Please confirm the eligibility declaration to proceed.';
    return;
  }

  // Pre-validate CNIC
  if (form.value.cnic_id) {
    const cnicDigits = form.value.cnic_id.replace(/\D/g, '');
    if (cnicDigits.length !== 13) {
      errorMsg.value = 'Please enter a complete 13-digit National ID / CNIC (e.g. 35201-1234567-1).';
      return;
    }
    const firstD = parseInt(cnicDigits[0]);
    if (firstD < 1 || firstD > 7) {
      errorMsg.value = 'Invalid CNIC: First digit (province territory code) must be between 1 and 7.';
      return;
    }
  }

  // Pre-validate Phone
  if (form.value.attendee_phone) {
    const phoneDigits = form.value.attendee_phone.replace(/\D/g, '');
    if (phoneDigits.length !== 11 || !phoneDigits.startsWith('03')) {
      errorMsg.value = 'Please enter a valid 11-digit Pakistani mobile number starting with 03 (e.g. 0300-1234567).';
      return;
    }
  }

  loading.value = true;
  errorMsg.value = '';
  try {
    const res = await api.post('/events/register', {
      event_id: props.event.id,
      attendee_name: form.value.attendee_name,
      attendee_phone: form.value.attendee_phone,
      cnic_id: form.value.cnic_id,
      age_group: form.value.age_group,
      health_concern: form.value.health_concern,
      attendee_email: form.value.attendee_email,
      attendees_count: form.value.attendees_count,
    });
    if (res.data.success) {
      passResult.value = res.data.registration;
      step.value = 2;
    }
  } catch (err) {
    console.error(err);
    errorMsg.value = err.response?.data?.message || 'Failed to complete registration.';
  } finally {
    loading.value = false;
  }
};

// Generate proper PDF using jsPDF
const downloadPdfPass = () => {
  if (!passResult.value) return;
  const p = passResult.value;

  // Format ISO date string to readable format e.g. "Wednesday, 09 Sep 2026"
  const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    try {
      const d = new Date(dateStr);
      return d.toLocaleDateString('en-GB', { weekday: 'long', day: '2-digit', month: 'short', year: 'numeric' });
    } catch {
      return dateStr;
    }
  };

  const campDate   = formatDate(p.event_date);
  const timeSlot   = p.time_slot   || 'N/A';
  const location   = p.location    || '450 Wellness Blvd, Medical Center';
  const eventTitle = p.event_title || 'Community Health Camp';
  const ageGroup   = p.age_group   || 'N/A';
  const concern    = p.health_concern || 'N/A';
  const persons    = String(p.attendees_count || 1);

  const doc = new jsPDF({ unit: 'mm', format: 'a4', orientation: 'portrait' });
  const pageW = 210;
  const marginX = 18;
  const contentW = pageW - marginX * 2;

  // ─── HEADER BACKGROUND (dark teal) ───────────────────────────────────────
  doc.setFillColor(13, 77, 74); // #0d4d4a
  doc.roundedRect(marginX, 12, contentW, 38, 5, 5, 'F');

  // Hospital Name
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(16);
  doc.setTextColor(255, 255, 255);
  doc.text('HealthPulse Hospital', marginX + 6, 26);

  // Subtitle
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8.5);
  doc.setTextColor(153, 246, 228); // teal-200
  doc.text('Official Free Community Health Camp Entry Pass', marginX + 6, 33);

  // FREE badge (top right)
  doc.setFillColor(16, 185, 129); // emerald-500
  doc.roundedRect(pageW - marginX - 32, 17, 30, 8, 3, 3, 'F');
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(7);
  doc.setTextColor(255, 255, 255);
  doc.text('100% FREE ($0)', pageW - marginX - 30.5, 22.5);

  // VERIFIED badge
  doc.setFillColor(6, 78, 59);
  doc.roundedRect(pageW - marginX - 32, 28, 30, 7, 2, 2, 'F');
  doc.setFontSize(6.5);
  doc.setTextColor(110, 231, 183);
  doc.text('✓  VERIFIED PASS', pageW - marginX - 30.5, 33);

  // ─── PASS CODE BOX ───────────────────────────────────────────────────────
  doc.setFillColor(240, 253, 250); // teal-50
  doc.setDrawColor(13, 148, 136);  // teal-500
  doc.setLineWidth(0.5);
  doc.roundedRect(marginX, 56, contentW, 22, 4, 4, 'FD');

  // Label above pass code
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(7);
  doc.setTextColor(15, 118, 110);
  doc.text('DIGITAL PASS ID / ENTRY CODE', pageW / 2, 63.5, { align: 'center' });

  // Pass code (large)
  doc.setFont('courier', 'bold');
  doc.setFontSize(22);
  doc.setTextColor(19, 78, 74); // teal-900
  doc.text(p.pass_code, pageW / 2, 73, { align: 'center' });

  // ─── SECTION: Details grid ───────────────────────────────────────────────
  const drawField = (label, value, x, y, w, h = 16) => {
    doc.setFillColor(248, 250, 252); // slate-50
    doc.setDrawColor(226, 232, 240); // slate-200
    doc.setLineWidth(0.3);
    doc.roundedRect(x, y, w, h, 2, 2, 'FD');

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(6.5);
    doc.setTextColor(100, 116, 139); // slate-500
    doc.text(label.toUpperCase(), x + 3, y + 5.5);

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(9);
    doc.setTextColor(15, 23, 42); // slate-900
    // split into multiple lines if needed
    const lines = doc.splitTextToSize(String(value), w - 6);
    doc.text(lines[0], x + 3, y + 12);
    if (lines[1]) doc.text(lines[1], x + 3, y + 15.5);
  };

  const halfW = (contentW - 4) / 2;
  let rowY = 84;

  // Row 1: Event name (full width)
  drawField('Medical Camp / Event Name', eventTitle, marginX, rowY, contentW, 18);
  rowY += 22;

  // Row 2: Attendee Name | CNIC
  drawField('Attendee Full Name', p.attendee_name || '', marginX, rowY, halfW);
  drawField('National ID / CNIC', p.cnic_id || '', marginX + halfW + 4, rowY, halfW);
  rowY += 20;

  // Row 3: Camp Date | Camp Time Slot (separate boxes so neither gets cut off)
  drawField('Camp Date', campDate, marginX, rowY, halfW);
  drawField('Camp Timings', timeSlot, marginX + halfW + 4, rowY, halfW);
  rowY += 20;

  // Row 4: Location (full width)
  drawField('Venue / Location', location, marginX, rowY, contentW);
  rowY += 20;

  // Row 5: Health Concern | Age Group
  drawField('Primary Health Concern', concern, marginX, rowY, halfW);
  drawField('Age Group', ageGroup, marginX + halfW + 4, rowY, halfW);
  rowY += 20;

  // Row 6: Registered Persons (half width)
  drawField('Registered Persons', `${persons} Person(s)`, marginX, rowY, halfW);
  rowY += 20;

  // ─── ELIGIBILITY NOTICE ───────────────────────────────────────────────────
  doc.setFillColor(254, 252, 232); // amber-50
  doc.setDrawColor(251, 191, 36);  // amber-400
  doc.setLineWidth(0.4);
  doc.roundedRect(marginX, rowY, contentW, 14, 3, 3, 'FD');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(7);
  doc.setTextColor(146, 64, 14); // amber-800
  doc.text('ELIGIBILITY: FREE Community Welfare Quota — No Payment Required', pageW / 2, rowY + 6, { align: 'center' });
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(6.5);
  doc.setTextColor(120, 53, 15);
  doc.text('This pass entitles the registered citizen(s) to FREE medical screening at the designated camp.', pageW / 2, rowY + 11, { align: 'center' });
  rowY += 18;

  // ─── FOOTER BAR ───────────────────────────────────────────────────────────
  doc.setFillColor(241, 245, 249); // slate-100
  doc.setDrawColor(203, 213, 225);
  doc.setLineWidth(0.3);
  doc.roundedRect(marginX, rowY, contentW, 18, 3, 3, 'FD');

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(7);
  doc.setTextColor(100, 116, 139);
  doc.text('Please present this pass (printed or on your phone screen) at the hospital reception on camp day.', pageW / 2, rowY + 6.5, { align: 'center' });
  doc.text('HealthPulse Hospital  •  450 Wellness Blvd, Medical Center, NY 10001  •  24/7 Helpline: +1 (800) 456-7890', pageW / 2, rowY + 12, { align: 'center' });

  // ─── SMALL PRINT (watermark line) ─────────────────────────────────────────
  doc.setFontSize(6);
  doc.setTextColor(200, 200, 200);
  doc.text(`Generated on ${new Date().toLocaleString()} | HealthPulse HMS © 2026`, pageW / 2, 285, { align: 'center' });

  doc.save(`HealthPulse_Camp_Pass_${p.pass_code}.pdf`);
  isDownloaded.value = true;
};

const handleClose = () => {
  step.value = 1;
  passResult.value = null;
  isDownloaded.value = false;
  emit('close');
};
</script>

<template>
  <div v-if="isOpen && event" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-md">
    <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95">
      
      <!-- Top Modal Header (Fixed at top) -->
      <div class="shrink-0 bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 text-white p-5 sm:p-6 flex justify-between items-start">
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="text-[10px] font-extrabold uppercase tracking-widest bg-emerald-500/20 border border-emerald-400/40 text-emerald-300 px-2.5 py-0.5 rounded-full flex items-center gap-1">
              <ShieldCheck class="w-3 h-3" /> Community Welfare Quota
            </span>
            <span class="text-[10px] font-bold text-teal-200">100% Free ($0)</span>
          </div>
          <h2 class="text-lg sm:text-xl font-extrabold text-white mt-1 leading-snug">
            Free Camp Registration & Verification
          </h2>
          <p class="text-xs text-teal-100 line-clamp-1">
            {{ event.title }}
          </p>
        </div>
        <button @click="handleClose" class="p-1.5 text-white/80 hover:text-white hover:bg-white/10 rounded-full transition">
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Step 1: Scrollable Form -->
      <div v-if="step === 1" class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-4">
        
        <!-- Camp Highlight Info Card -->
        <div class="bg-teal-50/80 border border-teal-200/80 rounded-2xl p-3.5 space-y-1.5 text-xs text-slate-700">
          <div class="flex items-center gap-2 text-teal-900 font-bold text-xs sm:text-sm">
            <Calendar class="w-4 h-4 text-teal-600 shrink-0" />
            <span>{{ event.event_date }}</span>
            <span class="text-slate-300">•</span>
            <Clock class="w-4 h-4 text-teal-600 shrink-0" />
            <span>{{ event.time_slot }}</span>
          </div>
          <div class="flex items-center gap-2 text-slate-600 text-[11px]">
            <MapPin class="w-3.5 h-3.5 text-slate-400 shrink-0" />
            <span>{{ event.location }}</span>
          </div>
          <div class="flex items-center justify-between pt-1 text-[11px] border-t border-teal-200/60 font-medium">
            <span>Organizer: <strong>{{ event.organizer }}</strong></span>
            <span class="font-black text-emerald-700 uppercase bg-emerald-100 px-2 py-0.5 rounded">Admission: Free ($0)</span>
          </div>
        </div>

        <!-- Authenticated Patient State or Sign-In Prompt -->
        <div v-if="authStore.isAuthenticated" class="bg-gradient-to-r from-emerald-50 to-teal-50/70 border border-emerald-200/90 rounded-2xl p-3 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-xs">
              <UserCheck class="w-4 h-4" />
            </div>
            <div>
              <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-800 block">Verified Patient Account</span>
              <span class="text-xs font-bold text-slate-800">{{ authStore.user?.name }} <span class="text-slate-400 font-normal">({{ authStore.user?.email }})</span></span>
            </div>
          </div>
          <span class="text-[10px] font-bold bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-lg border border-emerald-200">
            ✓ Auto-Verified
          </span>
        </div>
        <div v-else class="bg-slate-50 border border-slate-200 rounded-2xl p-2.5 flex items-center justify-between text-xs text-slate-600">
          <span class="text-[11px] text-slate-500">Already have a patient portal account?</span>
          <router-link to="/login" class="text-teal-700 font-bold hover:underline text-[11px] flex items-center gap-1">
            <LogIn class="w-3 h-3" /> Sign In to Auto-Fill →
          </router-link>
        </div>

        <div v-if="errorMsg" class="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl flex items-center gap-2">
          <AlertCircle class="w-4 h-4 shrink-0" />
          <span>{{ errorMsg }}</span>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-3.5">
          
          <!-- Attendee Full Name & Phone -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                Attendee Full Name <span class="text-red-500">*</span>
              </label>
              <input 
                type="text" 
                v-model="form.attendee_name"
                required
                placeholder="e.g. Sarah Jenkins"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500"
              />
            </div>

            <div>
              <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                Contact Phone <span class="text-red-500">*</span>
              </label>
              <input 
                type="tel" 
                v-model="form.attendee_phone"
                @input="handlePhoneInput"
                required
                maxlength="12"
                placeholder="0300-1234567"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500"
              />
            </div>
          </div>

          <!-- National ID & Age Group -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                National ID / CNIC <span class="text-red-500">*</span>
              </label>
              <input 
                type="text" 
                v-model="form.cnic_id"
                @input="handleCnicInput"
                required
                maxlength="15"
                placeholder="35201-1234567-1"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500"
              />
            </div>

            <div>
              <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                Age Group Category <span class="text-red-500">*</span>
              </label>
              <select 
                v-model="form.age_group"
                required
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                <option>Senior Citizen (50+ Years)</option>
                <option>Adult (18 - 49 Years)</option>
                <option>Child / Minor (Under 18)</option>
              </select>
            </div>
          </div>

          <!-- Health Concern & Attendees Count -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                Primary Health Concern <span class="text-red-500">*</span>
              </label>
              <select 
                v-model="form.health_concern"
                required
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                <option>Cardiac & Blood Pressure Checkup</option>
                <option>Diabetes / Blood Sugar Screening</option>
                <option>Pediatric & Child Growth Check</option>
                <option>Voluntary Blood Donation</option>
                <option>General Routine Health Check</option>
              </select>
            </div>

            <div>
              <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                Persons / Family Members
              </label>
              <select 
                v-model="form.attendees_count"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                <option :value="1">1 Person (Self)</option>
                <option :value="2">2 Persons</option>
                <option :value="3">3 Persons</option>
                <option :value="4">4 Persons</option>
                <option :value="5">5 Persons (Family Max)</option>
              </select>
            </div>
          </div>

          <!-- Email -->
          <div>
            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
              Email Address (Optional)
            </label>
            <input 
              type="email" 
              v-model="form.attendee_email"
              placeholder="email@example.com"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500"
            />
          </div>

          <!-- Eligibility Declaration Checkbox -->
          <div class="bg-amber-50/70 border border-amber-200/80 rounded-2xl p-2.5 flex items-start gap-2">
            <input 
              type="checkbox" 
              id="eligibility_check" 
              v-model="form.declaration"
              required
              class="mt-0.5 w-4 h-4 text-teal-600 rounded border-slate-300 focus:ring-teal-500"
            />
            <label for="eligibility_check" class="text-[10px] text-slate-700 leading-tight">
              <strong>Eligibility Declaration:</strong> I confirm that the details provided are accurate and the attendee qualifies for community subsidized/free health screening.
            </label>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
            <button 
              type="button" 
              @click="handleClose"
              class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold">
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="loading"
              class="flex items-center gap-1.5 bg-teal-800 hover:bg-teal-900 disabled:opacity-50 text-white font-bold text-xs uppercase tracking-wider py-2.5 px-5 rounded-xl shadow-lg shadow-teal-800/25 transition cursor-pointer">
              <ShieldCheck class="w-4 h-4 text-emerald-400" />
              <span v-if="loading">Verifying...</span>
              <span v-else>Verify & Get Free Pass →</span>
            </button>
          </div>
        </form>

      </div>

      <!-- Step 2: Confirmed Digital Entry Pass (Scrollable and Printable) -->
      <div v-else-if="step === 2 && passResult" class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-4">
        
        <div class="text-center space-y-1">
          <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto">
            <CheckCircle2 class="w-6 h-6" />
          </div>
          <h3 class="text-xl font-black text-slate-900">Registration Verified & Confirmed!</h3>
          <p class="text-[11px] text-slate-500">Your official digital camp admission pass has been generated.</p>
        </div>

        <!-- Boarding Pass Card -->
        <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-teal-950 text-white rounded-2xl p-5 shadow-2xl border-2 border-teal-400 relative overflow-hidden space-y-3">
          
          <!-- Pass Header -->
          <div class="flex items-center justify-between border-b border-teal-800/80 pb-2.5">
            <div>
              <span class="text-[9px] uppercase font-extrabold tracking-widest text-teal-300 block">HealthPulse Hospital</span>
              <h4 class="text-xs font-bold text-white">FREE CAMP ENTRY PASS</h4>
            </div>
            <span class="bg-emerald-500/20 border border-emerald-400 text-emerald-300 text-[10px] font-black px-2 py-0.5 rounded-full uppercase flex items-center gap-1">
              <CheckCircle2 class="w-3 h-3" /> VERIFIED
            </span>
          </div>

          <!-- Pass Number -->
          <div class="bg-white/10 rounded-xl p-3 flex items-center justify-between border border-white/10">
            <div>
              <span class="text-[9px] uppercase font-bold text-teal-200 block">Digital Pass ID</span>
              <span class="text-2xl font-black font-mono tracking-widest text-white">
                {{ passResult.pass_code }}
              </span>
            </div>
            <div class="text-right">
              <span class="text-[9px] uppercase font-bold text-emerald-300 block">Fee Waiver</span>
              <span class="text-xs font-black text-emerald-400 uppercase bg-emerald-950/60 px-2 py-0.5 rounded border border-emerald-500/40">100% FREE ($0)</span>
            </div>
          </div>

          <!-- Metadata Grid -->
          <div class="grid grid-cols-2 gap-2 text-xs text-teal-100 bg-slate-900/60 p-3 rounded-xl border border-teal-900/80">
            <div>
              <span class="text-[9px] text-slate-400 block">Attendee:</span>
              <strong class="text-white text-xs truncate block">{{ passResult.attendee_name }}</strong>
            </div>
            <div>
              <span class="text-[9px] text-slate-400 block">National ID / CNIC:</span>
              <strong class="text-teal-300 font-mono text-xs block">{{ passResult.cnic_id }}</strong>
            </div>
            <div>
              <span class="text-[9px] text-slate-400 block">Date & Time:</span>
              <strong class="text-white text-[10px] block">{{ passResult.event_date }} ({{ passResult.time_slot }})</strong>
            </div>
            <div>
              <span class="text-[9px] text-slate-400 block">Persons:</span>
              <strong class="text-emerald-300 text-xs block">{{ passResult.attendees_count }} Person(s)</strong>
            </div>
            <div class="col-span-2 pt-1 border-t border-teal-900/60">
              <span class="text-[9px] text-slate-400 block">Location:</span>
              <strong class="text-white text-[11px] block truncate">{{ passResult.location }}</strong>
            </div>
          </div>

          <div class="text-[10px] text-teal-300 text-center pt-1 border-t border-teal-800/80">
            Present this pass code at the entrance counter on the day of the camp.
          </div>
        </div>

        <!-- Action Buttons (Download PDF & Close) -->
        <div class="flex items-center gap-2 pt-2">
          <button 
            @click="downloadPdfPass"
            class="flex-1 flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider py-3 rounded-xl shadow-md transition cursor-pointer">
            <Check v-if="isDownloaded" class="w-4 h-4" />
            <Download v-else class="w-4 h-4" />
            <span>{{ isDownloaded ? 'Pass Downloaded ✓' : 'Download PDF Pass' }}</span>
          </button>
          
          <button 
            @click="handleClose"
            class="px-5 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs uppercase tracking-wider py-3 rounded-xl transition cursor-pointer">
            Done
          </button>
        </div>

      </div>

    </div>
  </div>
</template>

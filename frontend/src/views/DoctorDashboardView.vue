<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../services/api';
import { useAuthStore } from '../stores/authStore';
import { 
  Stethoscope, Users, Clock, CheckCircle2, Volume2, 
  FileText, Plus, Trash2, Heart, Activity, AlertCircle, Sparkles, Camera 
} from 'lucide-vue-next';

const authStore = useAuthStore();
const doctorData = ref(null);
const loading = ref(true);
const callingSuccess = ref('');
const rxError = ref('');
const rxSuccess = ref('');
const submittingRx = ref(false);
const updatingAvatar = ref(false);

const doctorDisplayName = computed(() => {
  const raw = doctorData.value?.doctor?.name || authStore.user?.name || '';

  if (!raw) return 'Consultant';

  let cleaned = raw.replace(/\s*\([^)]*\)/g, '').trim();
  cleaned = cleaned.replace(/^Dr\.?\s+/i, '').trim();

  return cleaned || 'Consultant';
});

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

const onAvatarError = (e) => {
  const currentSrc = e.target.src;
  const avatarPath = authStore.user?.avatar;

  // Production-safe relative storage path.
  // Example:
  // avatar = storage/avatars/doctor.jpg
  // browser requests:
  // /storage/avatars/doctor.jpg
  if (avatarPath && !currentSrc.startsWith('data:')) {
    const cleanPath = avatarPath.startsWith('/')
      ? avatarPath.substring(1)
      : avatarPath;

    e.target.src = `/${cleanPath}`;
    return;
  }

  // Final local/default fallback
  e.target.src = '/assets/images/doc1.jpg';
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

      if (res.success) {
        if (doctorData.value?.doctor) {
          doctorData.value.doctor.avatar = res.avatar;
        }
      } else {
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


// ===============================
// Prescription Form State
// ===============================

const activePatient = ref(null);

const rxForm = ref({
  diagnosis: 'Essential Consultation & Followup',
  symptoms_summary: '',
  bp: '120/80',
  pulse: '74 bpm',
  temperature: '98.6 F',
  weight: '70 kg',
  advice_notes: '',
  follow_up_date: '',
  items: [
    {
      medicine_name: 'Amoxicillin 500mg',
      dosage: '1 capsule',
      frequency: '1-0-1',
      duration: '5 days',
      instruction: 'After meals'
    },
    {
      medicine_name: 'Paracetamol 650mg',
      dosage: '1 tablet',
      frequency: '1-0-1 (if fever)',
      duration: '3 days',
      instruction: 'After food'
    }
  ]
});


// ===============================
// Fetch Doctor Dashboard Data
// ===============================

const fetchDoctorData = async () => {
  loading.value = true;

  try {
    const res = await api.get('/doctor/dashboard');

    if (res.data.success) {
      doctorData.value = res.data;

      // Auto-select calling patient or first patient in queue
      if (!activePatient.value) {
        const calling = res.data.active_queue?.find(
          q => q.status === 'calling'
        );

        if (calling) {
          selectPatient(calling);
        } else if (res.data.active_queue?.length > 0) {
          selectPatient(res.data.active_queue[0]);
        }
      }
    }
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};


// ===============================
// Select Patient
// ===============================

const selectPatient = (patientObj) => {
  activePatient.value = patientObj;

  rxForm.value.symptoms_summary =
    patientObj.symptoms ||
    patientObj.patient_notes ||
    '';

  rxError.value = '';
  rxSuccess.value = '';
};


// ===============================
// Call Patient
// ===============================

const callPatient = async (queueItem) => {
  callingSuccess.value = '';

  try {
    const res = await api.post('/doctor/call-patient', {
      queue_id: queueItem.id
    });

    if (res.data.success) {
      callingSuccess.value = res.data.message;

      selectPatient(queueItem);

      fetchDoctorData();
    }
  } catch (err) {
    console.error(err);
  }
};


// ===============================
// Prescription Medicine Rows
// ===============================

const addMedicineRow = () => {
  rxForm.value.items.push({
    medicine_name: '',
    dosage: '1 tablet',
    frequency: '1-0-1',
    duration: '5 days',
    instruction: 'After meals'
  });
};

const removeMedicineRow = (idx) => {
  rxForm.value.items.splice(idx, 1);
};


// ===============================
// Submit Prescription
// ===============================

const submitPrescription = async () => {
  if (!activePatient.value) {
    rxError.value =
      'Please select a patient from the OPD Queue first.';
    return;
  }

  if (
    !rxForm.value.diagnosis ||
    !rxForm.value.diagnosis.trim()
  ) {
    rxError.value =
      'Please specify a Clinical Diagnosis.';
    return;
  }

  rxSuccess.value = '';
  rxError.value = '';
  submittingRx.value = true;

  try {
    const payload = {
      patient_id:
        activePatient.value.patient_id ||
        activePatient.value.patient?.id ||
        null,

      appointment_id:
        activePatient.value.appointment_id ||
        (
          activePatient.value.symptoms !== undefined
            ? activePatient.value.id
            : null
        ),

      queue_id:
        activePatient.value.token_number
          ? activePatient.value.id
          : null,

      ...rxForm.value
    };

    const res = await api.post(
      '/prescriptions',
      payload
    );

    if (res.data.success) {
      rxSuccess.value =
        `Prescription ${
          res.data.prescription?.prescription_no || ''
        } finalized & Consultation marked as Completed!`;

      // Reset form fields
      rxForm.value.diagnosis =
        'Routine Health Checkup';

      rxForm.value.symptoms_summary = '';
      rxForm.value.advice_notes = '';
      rxForm.value.follow_up_date = '';

      await fetchDoctorData();
    }
  } catch (err) {
    console.error(
      'Prescription save error:',
      err
    );

    rxError.value =
      err.response?.data?.message ||
      'Error saving prescription. Please check that medicine names are filled.';
  } finally {
    submittingRx.value = false;
  }
};


// ===============================
// Component Mount
// ===============================

onMounted(() => {
  fetchDoctorData();
});
</script>

<template>
  <div class="min-h-screen bg-slate-100 py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- Top Doctor Info -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
          <div class="relative group">
            <img 
              :src="getAvatarUrl(authStore.user?.avatar)" 
              @error="onAvatarError"
              class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl object-cover border-2 border-brand-500 shadow-md bg-white"
            />
            <label 
              title="Change Profile Picture"
              class="absolute -bottom-1 -right-1 p-1.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl shadow-lg cursor-pointer transition-transform hover:scale-110">
              <Camera class="w-3.5 h-3.5" :class="{ 'animate-spin': updatingAvatar }" />
              <input type="file" accept="image/*" class="hidden" @change="handleAvatarUpload" />
            </label>
          </div>
          <div>
            <span class="text-xs font-bold uppercase tracking-wider text-brand-700 bg-brand-100 px-3 py-1 rounded-full">
              {{ doctorData?.doctor?.doctor_profile?.department?.name || authStore.user?.doctor_profile?.department?.name || 'Cardiology Faculty' }}
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">
              Hello, Dr. {{ doctorDisplayName }}
            </h1>
            <p class="text-xs text-slate-500">
              {{ doctorData?.doctor?.doctor_profile?.room_no || authStore.user?.doctor_profile?.room_no || 'Consultation Room' }} • {{ doctorData?.doctor?.doctor_profile?.qualification || authStore.user?.doctor_profile?.qualification || 'Specialist Consultant' }}
            </p>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="flex items-center gap-4 text-center">
          <div class="bg-white px-5 py-3 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-xs text-slate-400 block font-bold">Today's Queue</span>
            <span class="text-2xl font-black text-brand-600">{{ doctorData?.stats?.today_count || 0 }}</span>
          </div>
          <div class="bg-white px-5 py-3 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-xs text-slate-400 block font-bold">In Waiting</span>
            <span class="text-2xl font-black text-amber-600">{{ doctorData?.stats?.waiting_count || 0 }}</span>
          </div>
          <div class="bg-white px-5 py-3 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-xs text-slate-400 block font-bold">Completed</span>
            <span class="text-2xl font-black text-emerald-600">{{ doctorData?.stats?.completed_count || 0 }}</span>
          </div>
        </div>
      </div>

      <!-- Live Calling Banner Alert -->
      <div v-if="callingSuccess" class="mb-6 p-4 bg-emerald-600 text-white rounded-2xl shadow-lg flex items-center justify-between text-xs animate-in fade-in">
        <div class="flex items-center gap-2">
          <Volume2 class="w-5 h-5 animate-pulse" />
          <span class="font-bold">{{ callingSuccess }}</span>
        </div>
        <button @click="callingSuccess = ''" class="text-white/80 hover:text-white font-bold">Dismiss</button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: Today's Patient Queue List -->
        <div class="lg:col-span-4 space-y-4">
          <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
              <h3 class="text-base font-bold text-slate-900">Today's OPD Queue</h3>
              <span class="text-xs font-bold text-brand-600 bg-brand-50 px-2.5 py-1 rounded-full">
                {{ doctorData?.active_queue?.length || 0 }} Tokens
              </span>
            </div>

            <div class="space-y-3 max-h-[600px] overflow-y-auto pr-1">
              <div 
                v-for="q in doctorData?.active_queue" 
                :key="q.id"
                @click="selectPatient(doctorData.today_appointments.find(a => a.id === q.appointment_id) || q)"
                class="p-4 rounded-2xl border transition-all cursor-pointer"
                :class="q.status === 'calling' ? 'bg-brand-50 border-brand-300 ring-2 ring-brand-400/30' : 'bg-slate-50 border-slate-200 hover:bg-white'">
                
                <div class="flex items-center justify-between mb-2">
                  <span class="font-mono text-sm font-extrabold text-brand-700 bg-white px-2.5 py-0.5 rounded-lg border border-brand-200">
                    {{ q.token_number }}
                  </span>
                  <span 
                    class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full"
                    :class="{
                      'bg-emerald-100 text-emerald-700': q.status === 'completed',
                      'bg-amber-100 text-amber-700 animate-pulse': q.status === 'calling',
                      'bg-blue-100 text-blue-700': q.status === 'waiting',
                    }">
                    {{ q.status }}
                  </span>
                </div>

                <h4 class="text-sm font-bold text-slate-900">{{ q.patient_name }}</h4>
                <p class="text-[11px] text-slate-500">{{ q.patient_phone }}</p>

                <div class="mt-3 pt-2 border-t border-slate-200 flex items-center justify-between">
                  <button 
                    v-if="q.status === 'waiting'"
                    @click.stop="callPatient(q)"
                    class="flex items-center gap-1 bg-brand-600 hover:bg-brand-700 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg shadow-sm">
                    <Volume2 class="w-3.5 h-3.5" />
                    Call Next
                  </button>
                  <span v-else-if="q.status === 'calling'" class="text-[11px] font-bold text-amber-600">
                    Currently In Consultation
                  </span>
                  <span v-else class="text-[11px] text-slate-400 font-medium">Completed</span>
                </div>

              </div>
            </div>

          </div>
        </div>

        <!-- Right: Electronic Medical Record & Digital Prescription Builder -->
        <div class="lg:col-span-8">
          <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-sm space-y-6">
            
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
              <div>
                <h3 class="text-lg font-bold text-slate-900">
                  Electronic Medical Record & Prescription Builder
                </h3>
                <p class="text-xs text-slate-400">
                  Consultation for: <strong class="text-slate-800">{{ activePatient?.patient?.name || activePatient?.patient_name || 'Select Patient' }}</strong>
                  <span v-if="activePatient?.patient?.patient_profile?.mrn"> ({{ activePatient?.patient?.patient_profile?.mrn }})</span>
                </p>
              </div>

              <span class="text-xs font-bold bg-brand-50 text-brand-600 px-3 py-1 rounded-full">
                Token #{{ activePatient?.token_no || activePatient?.token_number || 'T-001' }}
              </span>
            </div>

            <div v-if="rxSuccess" class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl flex items-center gap-2">
              <CheckCircle2 class="w-5 h-5 flex-shrink-0 text-emerald-600" />
              <span class="font-bold">{{ rxSuccess }}</span>
            </div>

            <div v-if="rxError" class="p-4 bg-red-50 border border-red-200 text-red-800 text-xs rounded-2xl flex items-center gap-2">
              <AlertCircle class="w-5 h-5 flex-shrink-0 text-red-600" />
              <span class="font-bold">{{ rxError }}</span>
            </div>

            <!-- Patient Vitals Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 p-4 rounded-2xl border border-slate-200 text-xs">
              <div>
                <label class="text-slate-400 font-semibold block mb-1">Blood Pressure</label>
                <input type="text" v-model="rxForm.bp" class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 font-bold" />
              </div>
              <div>
                <label class="text-slate-400 font-semibold block mb-1">Pulse Rate</label>
                <input type="text" v-model="rxForm.pulse" class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 font-bold" />
              </div>
              <div>
                <label class="text-slate-400 font-semibold block mb-1">Temperature</label>
                <input type="text" v-model="rxForm.temperature" class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 font-bold" />
              </div>
              <div>
                <label class="text-slate-400 font-semibold block mb-1">Weight</label>
                <input type="text" v-model="rxForm.weight" class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 font-bold" />
              </div>
            </div>

            <!-- Clinical Diagnosis -->
            <div class="space-y-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                  Clinical Diagnosis <span class="text-red-500">*</span>
                </label>
                <input 
                  type="text" 
                  v-model="rxForm.diagnosis"
                  required
                  placeholder="e.g. Mild Hypertension, Acute Bronchitis, Post-Op Followup..."
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                  Symptoms & Observations
                </label>
                <textarea 
                  v-model="rxForm.symptoms_summary"
                  rows="2"
                  placeholder="Patient presented with complaints of..."
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white"
                ></textarea>
              </div>
            </div>

            <!-- Prescription Items Table -->
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Prescribed Medicines</h4>
                <button 
                  type="button" 
                  @click="addMedicineRow"
                  class="flex items-center gap-1 text-xs font-bold text-brand-600 hover:text-brand-700 bg-brand-50 px-3 py-1.5 rounded-lg">
                  <Plus class="w-3.5 h-3.5" />
                  Add Medicine
                </button>
              </div>

              <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                  <thead>
                    <tr class="text-slate-400 border-b border-slate-100">
                      <th class="pb-2 font-bold uppercase">Medicine Name</th>
                      <th class="pb-2 font-bold uppercase">Dosage</th>
                      <th class="pb-2 font-bold uppercase">Frequency</th>
                      <th class="pb-2 font-bold uppercase">Duration</th>
                      <th class="pb-2 font-bold uppercase">Instructions</th>
                      <th class="pb-2"></th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    <tr v-for="(item, idx) in rxForm.items" :key="idx">
                      <td class="py-2 pr-2">
                        <input type="text" v-model="item.medicine_name" placeholder="Medicine" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 font-semibold" />
                      </td>
                      <td class="py-2 pr-2">
                        <input type="text" v-model="item.dosage" placeholder="1 tab" class="w-24 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5" />
                      </td>
                      <td class="py-2 pr-2">
                        <input type="text" v-model="item.frequency" placeholder="1-0-1" class="w-24 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5" />
                      </td>
                      <td class="py-2 pr-2">
                        <input type="text" v-model="item.duration" placeholder="5 days" class="w-24 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5" />
                      </td>
                      <td class="py-2 pr-2">
                        <input type="text" v-model="item.instruction" placeholder="After food" class="w-32 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5" />
                      </td>
                      <td class="py-2 text-right">
                        <button @click="removeMedicineRow(idx)" class="text-red-400 hover:text-red-600 p-1">
                          <Trash2 class="w-4 h-4" />
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Advice & Followup -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Dietary & Clinical Advice</label>
                <textarea v-model="rxForm.advice_notes" rows="2" placeholder="Low sodium diet, plenty of hydration..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm"></textarea>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Follow-Up Date</label>
                <input type="date" v-model="rxForm.follow_up_date" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm" />
              </div>
            </div>

            <!-- Action Button -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
              <span class="text-xs text-slate-400">
                Finalizing prescription immediately marks visit as completed in OPD queue.
              </span>
              <button 
                @click="submitPrescription"
                :disabled="submittingRx"
                class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-xs uppercase tracking-wider py-3.5 px-8 rounded-xl shadow-lg shadow-emerald-500/25 transition-all">
                <FileText class="w-4 h-4" />
                <span v-if="submittingRx">Saving & Signing...</span>
                <span v-else>Sign & Finalize Prescription</span>
              </button>
            </div>

          </div>
        </div>

      </div>

    </div>
  </div>
</template>

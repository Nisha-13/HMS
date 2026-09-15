<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../services/api';
import { useAuthStore } from '../stores/authStore';
import {
  DollarSign, Users, UserCheck, BedDouble, Activity,
  Calendar, ShieldAlert, FileText, CheckCircle2, Clock,
  Search, Plus, Sparkles, RefreshCw, Stethoscope, Building,
  Ticket, Eye, EyeOff, ArrowUpRight, ShieldCheck, Phone, MapPin,
  Trash2, X, Upload, Check, AlertCircle, Layers, ChevronLeft, ChevronRight
} from 'lucide-vue-next';

const authStore = useAuthStore();
const analytics = ref(null);
const auditLogs = ref([]);
const doctorsList = ref([]);
const wardsList = ref([]);
const campRegistrations = ref([]);
const departmentsList = ref([]);
const servicesList = ref([]);
const eventsList = ref([]);
const loading = ref(true);

const adminDisplayName = computed(() => {
  const name = authStore.user?.name;
  if (!name) return 'Dr. Robert Harrison';

  const cleaned = name.replace(/\s*\([^)]*\)/g, '').trim();
  return cleaned || 'Dr. Robert Harrison';
});

// Recent Hospital OPD Registrations Pagination
const opdCurrentPage = ref(1);
const opdPerPage = ref(8);

const totalOpdPages = computed(() => {
  const total = analytics.value?.recent_appointments?.length || 0;
  return Math.max(1, Math.ceil(total / opdPerPage.value));
});

const paginatedOpdRegistrations = computed(() => {
  const list = analytics.value?.recent_appointments || [];
  const start = (opdCurrentPage.value - 1) * opdPerPage.value;

  return list.slice(start, start + opdPerPage.value);
});

const activeTab = ref('overview');
const doctorSearch = ref('');
const campSearch = ref('');
const deptSearch = ref('');
const serviceSearch = ref('');

const successMsg = ref('');
const errorMsg = ref('');

// Modals State
const isDoctorModalOpen = ref(false);
const showDoctorPassword = ref(false);
const docFormLoading = ref(false);

const newDoctor = ref({
  name: '',
  email: '',
  password: '',
  phone: '',
  department_id: '',
  specialty: '',
  qualification: 'MBBS, MD',
  experience_years: 5,
  consultation_fee: 50,
  room_no: 'Room 101',
  bio: '',
  avatar: '',
});

const docAvatarPreview = ref('');
const selectedDocPreset = ref('');

const docAvatarPresets = [
  { id: 'd1', label: 'Dr. John', url: '/assets/images/doc1.jpg' },
  { id: 'd2', label: 'Dr. Nancy', url: '/assets/images/doc2.jpg' },
  { id: 'd3', label: 'Dr. Michael', url: '/assets/images/doc3.jpg' },
  { id: 'd4', label: 'Dr. Sarah', url: '/assets/images/doc4.jpg' },
  { id: 'd5', label: 'Dr. David', url: '/assets/images/doc5.jpg' },
  { id: 'd6', label: 'Dr. Emily', url: '/assets/images/doc6.jpg' },
  { id: 'd7', label: 'Dr. Robert', url: '/assets/images/doc7.jpg' },
];

const handleDocFileUpload = (e) => {
  const file = e.target.files[0];

  if (!file) return;

  if (file.size > 3 * 1024 * 1024) {
    errorMsg.value = 'Doctor photo must be less than 3MB';
    return;
  }

  const reader = new FileReader();

  reader.onload = (event) => {
    docAvatarPreview.value = event.target.result;
    newDoctor.value.avatar = event.target.result;
    selectedDocPreset.value = '';
  };

  reader.readAsDataURL(file);
  e.target.value = '';
};

const selectDocPreset = (preset) => {
  selectedDocPreset.value = preset.id;
  docAvatarPreview.value = preset.url;
  newDoctor.value.avatar = preset.url;
};

const removeDocAvatar = () => {
  docAvatarPreview.value = '';
  selectedDocPreset.value = '';
  newDoctor.value.avatar = '';
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

/*
 * Production-friendly avatar fallback.
 *
 * We intentionally do not use:
 * http://127.0.0.1:8000/...
 *
 * The browser should request the same origin:
 * /storage/avatars/...
 *
 * In production:
 * https://your-domain.com/storage/avatars/...
 */
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

const isDeptModalOpen = ref(false);
const deptFormLoading = ref(false);

const newDept = ref({
  name: '',
  description: '',
  head_doctor_name: '',
  room_floor: '1st Floor',
  icon: 'Activity',
});

const isServiceModalOpen = ref(false);
const serviceFormLoading = ref(false);

const newService = ref({
  title: '',
  description: '',
  starting_price: 60,
  department_name: '',
  icon: 'Sparkles',
});

const isEventModalOpen = ref(false);
const eventFormLoading = ref(false);

const newEvent = ref({
  title: '',
  category: 'Free Medical Camp',
  event_date: '',
  time_slot: '09:00 AM - 04:00 PM',
  location: 'Hospital Main Community Hall',
  description: '',
  organizer: 'HealthPulse Medical Outreach',
});

const fetchAdminData = async () => {
  loading.value = true;

  try {
    const [
      resAnalytics,
      resLogs,
      resDocs,
      resWards,
      resCamps,
      resDepts,
      resServices,
      resEvents
    ] = await Promise.all([
      api.get('/admin/analytics'),
      api.get('/admin/audit-logs'),
      api.get('/doctors'),
      api.get('/wards'),
      api.get('/events/registrations'),
      api.get('/departments'),
      api.get('/services'),
      api.get('/events'),
    ]);

    if (resAnalytics.data.success) {
      analytics.value = resAnalytics.data;
    }

    if (resLogs.data.success) {
      auditLogs.value = resLogs.data.logs || [];
    }

    if (resDocs.data.success) {
      doctorsList.value = resDocs.data.doctors || [];
    }

    if (resWards.data.success) {
      wardsList.value = resWards.data.wards || [];
    }

    if (resCamps.data.success) {
      campRegistrations.value = resCamps.data.registrations || [];
    }

    if (resDepts.data.success) {
      departmentsList.value = resDepts.data.departments || [];
    }

    if (resServices.data.success) {
      servicesList.value = resServices.data.services || [];
    }

    if (resEvents.data.success) {
      eventsList.value = resEvents.data.events || [];
    }
  } catch (err) {
    console.error('Error fetching admin data:', err);
  } finally {
    loading.value = false;
  }
};

// DOCTOR ACTIONS
const submitNewDoctor = async () => {
  errorMsg.value = '';
  docFormLoading.value = true;

  try {
    const res = await api.post('/admin/doctors', newDoctor.value);

    if (res.data.success) {
      successMsg.value = res.data.message;
      isDoctorModalOpen.value = false;

      newDoctor.value = {
        name: '',
        email: '',
        password: '',
        phone: '',
        department_id: '',
        specialty: '',
        qualification: 'MBBS, MD',
        experience_years: 5,
        consultation_fee: 50,
        room_no: 'Room 101',
        bio: '',
        avatar: ''
      };

      docAvatarPreview.value = '';
      selectedDocPreset.value = '';

      await fetchAdminData();

      setTimeout(() => {
        successMsg.value = '';
      }, 4000);
    }
  } catch (err) {
    errorMsg.value =
      err.response?.data?.message || 'Failed to register doctor.';
  } finally {
    docFormLoading.value = false;
  }
};

const handleDeleteDoctor = async (id, name) => {
  if (!confirm(`Are you sure you want to remove Dr. ${name} from clinical faculty?`)) {
    return;
  }

  try {
    const res = await api.delete(`/admin/doctors/${id}`);

    if (res.data.success) {
      successMsg.value = res.data.message;

      await fetchAdminData();

      setTimeout(() => {
        successMsg.value = '';
      }, 4000);
    }
  } catch (err) {
    alert(
      err.response?.data?.message ||
      'Failed to remove doctor.'
    );
  }
};

// DEPARTMENT ACTIONS
const submitNewDepartment = async () => {
  errorMsg.value = '';
  deptFormLoading.value = true;

  try {
    const res = await api.post('/admin/departments', newDept.value);

    if (res.data.success) {
      successMsg.value = res.data.message;
      isDeptModalOpen.value = false;

      newDept.value = {
        name: '',
        description: '',
        head_doctor_name: '',
        room_floor: '1st Floor',
        icon: 'Activity'
      };

      await fetchAdminData();

      setTimeout(() => {
        successMsg.value = '';
      }, 4000);
    }
  } catch (err) {
    errorMsg.value =
      err.response?.data?.message ||
      'Failed to create department.';
  } finally {
    deptFormLoading.value = false;
  }
};

const handleDeleteDepartment = async (id, name) => {
  if (!confirm(`Are you sure you want to delete ${name} department?`)) {
    return;
  }

  try {
    const res = await api.delete(`/admin/departments/${id}`);

    if (res.data.success) {
      successMsg.value = res.data.message;

      await fetchAdminData();

      setTimeout(() => {
        successMsg.value = '';
      }, 4000);
    }
  } catch (err) {
    alert(
      err.response?.data?.message ||
      'Failed to delete department.'
    );
  }
};

// SERVICE ACTIONS
const submitNewService = async () => {
  errorMsg.value = '';
  serviceFormLoading.value = true;

  try {
    const res = await api.post('/admin/services', newService.value);

    if (res.data.success) {
      successMsg.value = res.data.message;
      isServiceModalOpen.value = false;

      newService.value = {
        title: '',
        description: '',
        starting_price: 60,
        department_name: '',
        icon: 'Sparkles'
      };

      await fetchAdminData();

      setTimeout(() => {
        successMsg.value = '';
      }, 4000);
    }
  } catch (err) {
    errorMsg.value =
      err.response?.data?.message ||
      'Failed to create service.';
  } finally {
    serviceFormLoading.value = false;
  }
};

const handleDeleteService = async (id, title) => {
  if (!confirm(`Are you sure you want to delete ${title}?`)) {
    return;
  }

  try {
    const res = await api.delete(`/admin/services/${id}`);

    if (res.data.success) {
      successMsg.value = res.data.message;

      await fetchAdminData();

      setTimeout(() => {
        successMsg.value = '';
      }, 4000);
    }
  } catch (err) {
    alert(
      err.response?.data?.message ||
      'Failed to delete service.'
    );
  }
};

// EVENT ACTIONS
const submitNewEvent = async () => {
  errorMsg.value = '';
  eventFormLoading.value = true;

  try {
    const res = await api.post('/admin/events', newEvent.value);

    if (res.data.success) {
      successMsg.value = res.data.message;
      isEventModalOpen.value = false;

      newEvent.value = {
        title: '',
        category: 'Free Medical Camp',
        event_date: '',
        time_slot: '09:00 AM - 04:00 PM',
        location: 'Hospital Main Community Hall',
        description: '',
        organizer: 'HealthPulse Medical Outreach'
      };

      await fetchAdminData();

      setTimeout(() => {
        successMsg.value = '';
      }, 4000);
    }
  } catch (err) {
    errorMsg.value =
      err.response?.data?.message ||
      'Failed to schedule event.';
  } finally {
    eventFormLoading.value = false;
  }
};

const handleDeleteEvent = async (id, title) => {
  if (!confirm(`Are you sure you want to delete ${title}?`)) {
    return;
  }

  try {
    const res = await api.delete(`/admin/events/${id}`);

    if (res.data.success) {
      successMsg.value = res.data.message;

      await fetchAdminData();

      setTimeout(() => {
        successMsg.value = '';
      }, 4000);
    }
  } catch (err) {
    alert(
      err.response?.data?.message ||
      'Failed to delete event.'
    );
  }
};

// COMPUTED FILTERS
const filteredDoctors = computed(() => {
  if (!doctorSearch.value.trim()) {
    return doctorsList.value;
  }

  const q = doctorSearch.value.toLowerCase();

  return doctorsList.value.filter(d =>
    (d.name || '').toLowerCase().includes(q) ||
    (d.doctor_profile?.department?.name || '').toLowerCase().includes(q) ||
    (d.doctor_profile?.qualification || '').toLowerCase().includes(q) ||
    (d.doctor_profile?.room_no || '').toLowerCase().includes(q)
  );
});

const filteredCamps = computed(() => {
  if (!campSearch.value.trim()) {
    return campRegistrations.value;
  }

  const q = campSearch.value.toLowerCase();

  return campRegistrations.value.filter(c =>
    (c.pass_code || '').toLowerCase().includes(q) ||
    (c.attendee_name || '').toLowerCase().includes(q) ||
    (c.cnic_id || '').toLowerCase().includes(q) ||
    (c.event?.title || '').toLowerCase().includes(q)
  );
});

const filteredDepartments = computed(() => {
  if (!deptSearch.value.trim()) {
    return departmentsList.value;
  }

  const q = deptSearch.value.toLowerCase();

  return departmentsList.value.filter(d =>
    (d.name || '').toLowerCase().includes(q) ||
    (d.head_doctor_name || '').toLowerCase().includes(q) ||
    (d.description || '').toLowerCase().includes(q)
  );
});

const filteredServices = computed(() => {
  if (!serviceSearch.value.trim()) {
    return servicesList.value;
  }

  const q = serviceSearch.value.toLowerCase();

  return servicesList.value.filter(s =>
    (s.title || '').toLowerCase().includes(q) ||
    (s.department_name || '').toLowerCase().includes(q) ||
    (s.description || '').toLowerCase().includes(q)
  );
});

onMounted(() => {
  fetchAdminData();
});
</script>

<template>
  <div class="min-h-screen bg-slate-100 py-8 sm:py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
      
      <!-- Top Title & Controls Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-purple-700 bg-purple-100 px-3 py-1 rounded-full">
            Executive Hospital Administration
          </span>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">
            HealthPulse Command Center
          </h1>
          <p class="text-xs text-slate-600 mt-1 flex items-center gap-1.5 flex-wrap">
            <span class="text-purple-700 font-bold">Hello, {{ adminDisplayName }}</span>
            <span class="text-slate-300">•</span>
            <span class="text-slate-500">Hospital Administrator & Medical Director</span>
          </p>
        </div>

        <div class="flex items-center gap-3">
          <button 
            @click="fetchAdminData" 
            class="flex items-center gap-1.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold py-2.5 px-4 rounded-xl shadow-sm transition-all cursor-pointer">
            <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" />
            <span>Refresh</span>
          </button>
          <router-link 
            to="/queue-display" 
            target="_blank"
            class="flex items-center gap-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold py-2.5 px-4 rounded-xl shadow-sm transition">
            <span>Live OPD TV Screen ↗</span>
          </router-link>
        </div>
      </div>

      <!-- Feedback Alerts -->
      <div v-if="successMsg" class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl flex items-center gap-2 shadow-sm animate-fade-in">
        <CheckCircle2 class="w-4 h-4 flex-shrink-0 text-emerald-600" />
        <span>{{ successMsg }}</span>
      </div>

      <div v-if="errorMsg" class="p-4 bg-red-50 border border-red-200 text-red-700 text-xs rounded-2xl flex items-center gap-2 shadow-sm animate-fade-in">
        <AlertCircle class="w-4 h-4 flex-shrink-0" />
        <span>{{ errorMsg }}</span>
      </div>

      <!-- KPI Overview Cards -->
      <div v-if="analytics" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 sm:gap-6">
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm flex items-center justify-between">
          <div>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Hospital Revenue</span>
            <span class="text-2xl sm:text-3xl font-black text-slate-900 mt-1 block">
              ${{ analytics.kpis?.total_revenue?.toLocaleString() }}
            </span>
            <span class="text-[11px] text-emerald-600 font-semibold mt-1 block">Paid Invoices</span>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
            <DollarSign class="w-6 h-6" />
          </div>
        </div>

        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm flex items-center justify-between">
          <div>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Today's Consultations</span>
            <span class="text-2xl sm:text-3xl font-black text-slate-900 mt-1 block">
              {{ analytics.kpis?.today_appointments }}
            </span>
            <span class="text-[11px] text-brand-600 font-semibold mt-1 block">{{ analytics.kpis?.live_queue_waiting }} In Live Queue</span>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center">
            <Calendar class="w-6 h-6" />
          </div>
        </div>

        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm flex items-center justify-between">
          <div>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Bed Occupancy Rate</span>
            <span class="text-2xl sm:text-3xl font-black text-slate-900 mt-1 block">
              {{ analytics.kpis?.bed_occupancy_rate }}%
            </span>
            <span class="text-[11px] text-slate-500 mt-1 block">{{ analytics.kpis?.occupied_beds }} / {{ analytics.kpis?.total_beds }} Inpatient Beds</span>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center">
            <BedDouble class="w-6 h-6" />
          </div>
        </div>

        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm flex items-center justify-between">
          <div>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Clinical Faculty</span>
            <span class="text-2xl sm:text-3xl font-black text-slate-900 mt-1 block">
              {{ doctorsList.length }} Doctors
            </span>
            <span class="text-[11px] text-purple-600 font-semibold mt-1 block">{{ departmentsList.length }} Clinical Departments</span>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
            <UserCheck class="w-6 h-6" />
          </div>
        </div>
      </div>

      <!-- Navigation Tabs Bar (Scrollable on Mobile) -->
      <div class="overflow-x-auto pb-2 max-w-full">
        <div class="flex items-center gap-2 min-w-max border-b border-slate-200 pb-3">
          <button 
            @click="activeTab = 'overview'"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer"
            :class="activeTab === 'overview' ? 'bg-purple-700 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'">
            <Activity class="w-4 h-4" />
            <span>Operations Feed</span>
          </button>

          <button 
            @click="activeTab = 'doctors'"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer"
            :class="activeTab === 'doctors' ? 'bg-purple-700 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'">
            <Stethoscope class="w-4 h-4" />
            <span>Doctors Faculty ({{ doctorsList.length }})</span>
          </button>

          <button 
            @click="activeTab = 'departments'"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer"
            :class="activeTab === 'departments' ? 'bg-purple-700 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'">
            <Building class="w-4 h-4" />
            <span>Departments ({{ departmentsList.length }})</span>
          </button>

          <button 
            @click="activeTab = 'services'"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer"
            :class="activeTab === 'services' ? 'bg-purple-700 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'">
            <Layers class="w-4 h-4" />
            <span>Services & Facilities ({{ servicesList.length }})</span>
          </button>

          <button 
            @click="activeTab = 'events'"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer"
            :class="activeTab === 'events' ? 'bg-purple-700 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'">
            <Sparkles class="w-4 h-4 text-teal-400" />
            <span>Health Events & Camps ({{ eventsList.length }})</span>
          </button>

          <button 
            @click="activeTab = 'camps'"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer"
            :class="activeTab === 'camps' ? 'bg-purple-700 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'">
            <Ticket class="w-4 h-4" />
            <span>Camp Passes ({{ campRegistrations.length }})</span>
          </button>

          <button 
            @click="activeTab = 'wards'"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer"
            :class="activeTab === 'wards' ? 'bg-purple-700 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'">
            <BedDouble class="w-4 h-4" />
            <span>Inpatient Wards ({{ wardsList.length }})</span>
          </button>

          <button 
            @click="activeTab = 'logs'"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer"
            :class="activeTab === 'logs' ? 'bg-purple-700 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'">
            <ShieldAlert class="w-4 h-4" />
            <span>Audit Logs ({{ auditLogs.length }})</span>
          </button>
        </div>
      </div>

      <!-- TAB 1: Live Overview & Feed -->
      <div v-show="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-8 bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-sm space-y-6">
          <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
              <h3 class="text-lg font-bold text-slate-900">Recent Hospital OPD Registrations</h3>
              <p class="text-xs text-slate-400">Live clinical consultations and patient tokens</p>
            </div>
            <span class="text-xs font-bold text-brand-600 bg-brand-50 px-3 py-1 rounded-full">
              Live Feed
            </span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="text-slate-400 border-b border-slate-100 pb-2">
                  <th class="pb-3 font-bold uppercase tracking-wider">Token / ID</th>
                  <th class="pb-3 font-bold uppercase tracking-wider">Patient Name</th>
                  <th class="pb-3 font-bold uppercase tracking-wider">Specialist Doctor</th>
                  <th class="pb-3 font-bold uppercase tracking-wider">Date & Time</th>
                  <th class="pb-3 font-bold uppercase tracking-wider">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="apt in paginatedOpdRegistrations" :key="apt.id" class="hover:bg-slate-50/80 transition-colors">
                  <td class="py-3.5 font-mono font-bold text-brand-700">{{ apt.token_no || apt.appointment_no }}</td>
                  <td class="py-3.5 font-bold text-slate-900">{{ apt.patient?.name || 'Walk-in Patient' }}</td>
                  <td class="py-3.5 text-slate-600">{{ apt.doctor?.name }} ({{ apt.doctor?.doctor_profile?.department?.name || 'Specialist' }})</td>
                  <td class="py-3.5 text-slate-500">{{ apt.appointment_date }} • {{ apt.time_slot }}</td>
                  <td class="py-3.5">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase"
                      :class="{
                        'bg-emerald-100 text-emerald-700': apt.status === 'completed',
                        'bg-blue-100 text-blue-700': apt.status === 'confirmed',
                        'bg-amber-100 text-amber-700 animate-pulse': apt.status === 'in_consultation',
                        'bg-red-100 text-red-700': apt.status === 'cancelled',
                      }">
                      {{ apt.status }}
                    </span>
                  </td>
                </tr>
                <tr v-if="!analytics?.recent_appointments || analytics?.recent_appointments.length === 0">
                  <td colspan="5" class="py-6 text-center text-slate-400">
                    No OPD registrations recorded yet.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- OPD Registrations Pagination Controls -->
          <div v-if="analytics?.recent_appointments && analytics?.recent_appointments.length > 0" class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <span class="text-slate-500 font-medium">
              Showing <span class="font-bold text-slate-800">{{ (opdCurrentPage - 1) * opdPerPage + 1 }}</span> to 
              <span class="font-bold text-slate-800">{{ Math.min(opdCurrentPage * opdPerPage, analytics.recent_appointments.length) }}</span> 
              of <span class="font-bold text-slate-800">{{ analytics.recent_appointments.length }}</span> OPD tokens
            </span>

            <div v-if="totalOpdPages > 1" class="flex items-center gap-1.5">
              <button 
                type="button"
                @click="opdCurrentPage = Math.max(1, opdCurrentPage - 1)"
                :disabled="opdCurrentPage === 1"
                class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none transition cursor-pointer"
                title="Previous Page">
                <ChevronLeft class="w-4 h-4" />
              </button>

              <div class="flex items-center gap-1">
                <button 
                  v-for="page in totalOpdPages" 
                  :key="page"
                  type="button"
                  @click="opdCurrentPage = page"
                  class="w-7 h-7 rounded-lg text-xs font-bold transition flex items-center justify-center cursor-pointer"
                  :class="opdCurrentPage === page ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'">
                  {{ page }}
                </button>
              </div>

              <button 
                type="button"
                @click="opdCurrentPage = Math.min(totalOpdPages, opdCurrentPage + 1)"
                :disabled="opdCurrentPage === totalOpdPages"
                class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none transition cursor-pointer"
                title="Next Page">
                <ChevronRight class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Right: Recent Security Logs -->
        <div class="lg:col-span-4 bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-sm space-y-4">
          <div class="border-b border-slate-100 pb-4">
            <h3 class="text-base font-bold text-slate-900">Hospital Security Activity</h3>
            <p class="text-xs text-slate-400">Security and action trail events</p>
          </div>

          <div class="space-y-3 max-h-[420px] overflow-y-auto pr-1">
            <div 
              v-for="log in auditLogs.slice(0, 8)" 
              :key="log.id"
              class="p-3 bg-slate-50 rounded-2xl border border-slate-100 space-y-1 text-xs">
              <div class="flex items-center justify-between">
                <span class="font-bold text-purple-700 uppercase tracking-wider text-[10px]">
                  {{ log.action }}
                </span>
                <span class="text-[10px] text-slate-400">
                  {{ new Date(log.created_at).toLocaleTimeString() }}
                </span>
              </div>
              <p class="text-slate-600 text-[11px]">
                Initiated by <strong>{{ log.user?.name || 'System Administrator' }}</strong>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: Doctors & Clinical Faculty Management -->
      <div v-show="activeTab === 'doctors'" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
          <div>
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
              <Stethoscope class="w-5 h-5 text-purple-600" />
              <span>Clinical Faculty Directory ({{ doctorsList.length }})</span>
            </h3>
            <p class="text-xs text-slate-400">Manage hospital specialists, consulting rooms, qualifications, and consultation fees</p>
          </div>
          
          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="relative w-full sm:w-64">
              <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
              <input 
                type="text"
                v-model="doctorSearch"
                placeholder="Search doctor by name, specialty..."
                class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500"
              />
            </div>

            <!-- Add Doctor Button -->
            <button 
              @click="isDoctorModalOpen = true"
              class="flex items-center justify-center gap-1.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-xs font-bold py-2.5 px-4 rounded-xl shadow-md transition cursor-pointer">
              <Plus class="w-4 h-4" />
              <span>Add New Doctor</span>
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="doc in filteredDoctors" 
            :key="doc.id"
            class="p-5 rounded-2xl border border-slate-200 bg-slate-50 hover:bg-white hover:shadow-md transition space-y-3 relative group">
            
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <img 
                  :src="getAvatarUrl(doc.avatar)" 
                  @error="onDoctorImgError($event, doc.avatar)"
                  class="w-12 h-12 rounded-xl object-cover border border-purple-300 bg-white"
                />
                <div>
                  <h4 class="text-sm font-bold text-slate-900">{{ doc.name }}</h4>
                  <span class="text-[11px] font-semibold text-purple-700 bg-purple-100 px-2 py-0.5 rounded">
                    {{ doc.doctor_profile?.department?.name || 'Specialist' }}
                  </span>
                </div>
              </div>

              <!-- Delete Doctor Button -->
              <button 
                @click="handleDeleteDoctor(doc.id, doc.name)"
                title="Remove Doctor"
                class="opacity-0 group-hover:opacity-100 p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition cursor-pointer">
                <Trash2 class="w-4 h-4" />
              </button>
            </div>

            <div class="text-xs text-slate-600 space-y-1 pt-2 border-t border-slate-200">
              <p><strong>Qualification:</strong> {{ doc.doctor_profile?.qualification || 'MD, MBBS' }}</p>
              <p><strong>Room / Clinic:</strong> {{ doc.doctor_profile?.room_no || 'Room 101' }}</p>
              <p><strong>Experience:</strong> {{ doc.doctor_profile?.experience_years || 8 }}+ Years</p>
              <p class="text-emerald-700 font-bold"><strong>Consultation Fee:</strong> ${{ doc.doctor_profile?.consultation_fee || 50 }}</p>
              <p class="text-slate-400 text-[11px]">Email: {{ doc.email }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 3: Departments Management -->
      <div v-show="activeTab === 'departments'" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
          <div>
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
              <Building class="w-5 h-5 text-purple-600" />
              <span>Hospital Departments Management ({{ departmentsList.length }})</span>
            </h3>
            <p class="text-xs text-slate-400">Configure medical faculties, head physicians, and floor allocations</p>
          </div>
          
          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="relative w-full sm:w-64">
              <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
              <input 
                type="text"
                v-model="deptSearch"
                placeholder="Search departments..."
                class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500"
              />
            </div>

            <button 
              @click="isDeptModalOpen = true"
              class="flex items-center justify-center gap-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold py-2.5 px-4 rounded-xl shadow transition cursor-pointer">
              <Plus class="w-4 h-4" />
              <span>Add Department</span>
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="dept in filteredDepartments" 
            :key="dept.id"
            class="p-5 rounded-2xl border border-slate-200 bg-slate-50 hover:bg-white hover:shadow-md transition space-y-3 relative group">
            <div class="flex items-center justify-between">
              <h4 class="font-bold text-slate-900 text-sm">{{ dept.name }}</h4>
              <button 
                @click="handleDeleteDepartment(dept.id, dept.name)"
                title="Delete Department"
                class="opacity-0 group-hover:opacity-100 p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer">
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
            <p class="text-xs text-slate-500 line-clamp-2">{{ dept.description }}</p>
            <div class="pt-2 border-t border-slate-200 flex items-center justify-between text-xs text-slate-600">
              <span>Head: <strong>{{ dept.head_doctor_name }}</strong></span>
              <span class="px-2 py-0.5 rounded bg-purple-100 text-purple-700 font-bold text-[10px]">{{ dept.room_floor }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 4: Services Management -->
      <div v-show="activeTab === 'services'" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
          <div>
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
              <Layers class="w-5 h-5 text-purple-600" />
              <span>Hospital Diagnostic & Clinical Services ({{ servicesList.length }})</span>
            </h3>
            <p class="text-xs text-slate-400">Configure medical procedures, laboratory diagnostics, and starting rates</p>
          </div>
          
          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="relative w-full sm:w-64">
              <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
              <input 
                type="text"
                v-model="serviceSearch"
                placeholder="Search services..."
                class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500"
              />
            </div>

            <button 
              @click="isServiceModalOpen = true"
              class="flex items-center justify-center gap-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold py-2.5 px-4 rounded-xl shadow transition cursor-pointer">
              <Plus class="w-4 h-4" />
              <span>Add Service</span>
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="srv in filteredServices" 
            :key="srv.id"
            class="p-5 rounded-2xl border border-slate-200 bg-slate-50 hover:bg-white hover:shadow-md transition space-y-3 relative group">
            <div class="flex items-center justify-between">
              <h4 class="font-bold text-slate-900 text-sm">{{ srv.title }}</h4>
              <button 
                @click="handleDeleteService(srv.id, srv.title)"
                title="Delete Service"
                class="opacity-0 group-hover:opacity-100 p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer">
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
            <p class="text-xs text-slate-500 line-clamp-2">{{ srv.description }}</p>
            <div class="pt-2 border-t border-slate-200 flex items-center justify-between text-xs">
              <span class="text-slate-500">{{ srv.department_name }}</span>
              <span class="font-black text-emerald-700 text-sm">${{ srv.starting_price }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 5: Health Events & Camps Management -->
      <div v-show="activeTab === 'events'" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
          <div>
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
              <Sparkles class="w-5 h-5 text-teal-600" />
              <span>Hospital Health Events & Medical Outreach ({{ eventsList.length }})</span>
            </h3>
            <p class="text-xs text-slate-400">Schedule community health camps, blood donation drives, and seminar programs</p>
          </div>
          
          <button 
            @click="isEventModalOpen = true"
            class="flex items-center justify-center gap-1.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold py-2.5 px-4 rounded-xl shadow transition cursor-pointer">
            <Plus class="w-4 h-4" />
            <span>Schedule New Event</span>
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="ev in eventsList" 
            :key="ev.id"
            class="p-5 rounded-2xl border border-slate-200 bg-slate-50 hover:bg-white hover:shadow-md transition space-y-3 relative group">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-bold uppercase px-2.5 py-0.5 rounded-full bg-teal-100 text-teal-800">
                {{ ev.category }}
              </span>
              <button 
                @click="handleDeleteEvent(ev.id, ev.title)"
                title="Delete Event"
                class="opacity-0 group-hover:opacity-100 p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer">
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
            <h4 class="font-bold text-slate-900 text-sm">{{ ev.title }}</h4>
            <p class="text-xs text-slate-500 line-clamp-2">{{ ev.description }}</p>
            <div class="pt-2 border-t border-slate-200 text-xs text-slate-600 space-y-1">
              <p>📅 {{ ev.event_date }} • {{ ev.time_slot }}</p>
              <p>📍 {{ ev.location }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 6: Inpatient Wards Matrix -->
      <div v-show="activeTab === 'wards'" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-sm space-y-6">
        <div class="border-b border-slate-100 pb-4">
          <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <Building class="w-5 h-5 text-purple-600" />
            Hospital Inpatient Wards & Smart Bed Matrix
          </h3>
          <p class="text-xs text-slate-400">Real-time ward capacity, ICU occupancy, and bed allocation status</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="ward in wardsList" 
            :key="ward.id"
            class="p-5 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
            <div class="flex items-center justify-between">
              <h4 class="font-bold text-slate-900 text-sm">{{ ward.name }}</h4>
              <span class="text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-full bg-cyan-100 text-cyan-800">
                Floor {{ ward.floor }}
              </span>
            </div>
            <p class="text-xs text-slate-500 line-clamp-1">{{ ward.description || 'Specialized inpatient care ward' }}</p>
            <div class="pt-2 border-t border-slate-200 flex items-center justify-between text-xs">
              <span>Occupied: <strong>{{ ward.beds ? ward.beds.filter(b => b.is_occupied).length : 0 }} Beds</strong></span>
              <span class="text-emerald-700 font-bold">Total: {{ ward.total_beds }} Beds</span>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 7: Free Camps Passes -->
      <div v-show="activeTab === 'camps'" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
          <div>
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
              <Sparkles class="w-5 h-5 text-teal-600" />
              Community Health Camp Passes & Attendee Database
            </h3>
            <p class="text-xs text-slate-400">Total free community screening passes issued across all hospital outreach programs</p>
          </div>
          
          <div class="relative w-full sm:w-80">
            <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
            <input 
              type="text"
              v-model="campSearch"
              placeholder="Search camp pass, name, CNIC..."
              class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500"
            />
          </div>
        </div>

        <div v-if="filteredCamps.length > 0" class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="text-slate-400 border-b border-slate-100 uppercase font-bold text-[11px]">
                <th class="pb-3">Pass ID</th>
                <th class="pb-3">Attendee Name</th>
                <th class="pb-3">National ID / CNIC</th>
                <th class="pb-3">Camp Event</th>
                <th class="pb-3">Health Concern</th>
                <th class="pb-3">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="c in filteredCamps" :key="c.id" class="hover:bg-slate-50 transition">
                <td class="py-3 font-mono font-bold text-teal-700">{{ c.pass_code }}</td>
                <td class="py-3 font-bold text-slate-900">{{ c.attendee_name }}</td>
                <td class="py-3 font-mono text-slate-600">{{ c.cnic_id }}</td>
                <td class="py-3 text-slate-700">{{ c.event?.title || 'Community Health Camp' }}</td>
                <td class="py-3 text-slate-600">{{ c.health_concern }}</td>
                <td class="py-3">
                  <span 
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase"
                    :class="c.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                    {{ c.status || 'CONFIRMED' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="py-10 text-center text-slate-400 text-xs">No camp registrations found.</div>
      </div>

      <!-- TAB 8: Security Audit Logs -->
      <div v-show="activeTab === 'logs'" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-sm space-y-6">
        <div class="border-b border-slate-100 pb-4">
          <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <ShieldAlert class="w-5 h-5 text-purple-600" />
            Full System Security & Compliance Audit Log
          </h3>
          <p class="text-xs text-slate-400">HIPAA & JCI compliant immutable action trail</p>
        </div>

        <div class="space-y-3">
          <div 
            v-for="log in auditLogs" 
            :key="log.id"
            class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
            <div>
              <span class="font-bold text-purple-800 uppercase tracking-wider text-[11px] block">{{ log.action }}</span>
              <p class="text-slate-600 text-xs mt-0.5">
                Actor: <strong>{{ log.user?.name || 'Hospital Admin' }}</strong> ({{ log.user?.email || 'System' }})
              </p>
            </div>
            <span class="text-slate-400 font-mono text-[11px]">
              {{ new Date(log.created_at).toLocaleString() }}
            </span>
          </div>
        </div>
      </div>

    </div>

    <!-- MODAL 1: ADD NEW DOCTOR (Fully Responsive & Accessible on Small Screens) -->
    <div v-if="isDoctorModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-md p-2 sm:p-4 md:p-6 flex items-start sm:items-center justify-center">
      <div class="relative w-full max-w-xl bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-100 my-2 sm:my-auto max-h-[calc(100dvh-1rem)] sm:max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95">
        
        <!-- Sticky Header -->
        <div class="p-3.5 sm:p-5 border-b border-slate-100 flex items-center justify-between flex-shrink-0 bg-white">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center shadow-sm">
              <Stethoscope class="w-4 h-4 sm:w-5 sm:h-5" />
            </div>
            <div>
              <h3 class="text-sm sm:text-lg font-bold text-slate-900">Add Specialist Doctor</h3>
              <p class="text-[10px] sm:text-xs text-slate-400">Appoint new physician to clinical faculty</p>
            </div>
          </div>
          <button @click="isDoctorModalOpen = false" class="p-1.5 sm:p-2 text-slate-400 hover:text-slate-700 rounded-xl hover:bg-slate-100 transition cursor-pointer">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Scrollable Form Body -->
        <form @submit.prevent="submitNewDoctor" class="flex flex-col flex-1 min-h-0 overflow-hidden">
          <div class="p-3.5 sm:p-6 overflow-y-auto flex-1 min-h-0 space-y-3 sm:space-y-4">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Doctor Full Name</label>
                <input 
                  type="text" 
                  v-model="newDoctor.name" 
                  required 
                  placeholder="Dr. Sarah Jenkins"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-purple-500"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Email (Login ID)</label>
                <input 
                  type="email" 
                  v-model="newDoctor.email" 
                  required 
                  placeholder="dr.sarah@healthpulse.com"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-purple-500"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
              <!-- Password with Eye Icon -->
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Password</label>
                <div class="relative">
                  <input 
                    :type="showDoctorPassword ? 'text' : 'password'" 
                    v-model="newDoctor.password" 
                    required 
                    minlength="6"
                    placeholder="••••••••"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-3.5 pr-10 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-purple-500"
                  />
                  <button 
                    type="button"
                    @click="showDoctorPassword = !showDoctorPassword"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-purple-600 focus:outline-none cursor-pointer">
                    <EyeOff v-if="showDoctorPassword" class="w-4 h-4 text-purple-600" />
                    <Eye v-else class="w-4 h-4" />
                  </button>
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Phone Number</label>
                <input 
                  type="text" 
                  v-model="newDoctor.phone" 
                  placeholder="+1 (555) 432-1098"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-purple-500"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Department</label>
                <select 
                  v-model="newDoctor.department_id" 
                  required
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-purple-500">
                  <option value="" disabled>Select Department</option>
                  <option v-for="dept in departmentsList" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Qualification</label>
                <input 
                  type="text" 
                  v-model="newDoctor.qualification" 
                  required 
                  placeholder="MBBS, MD, FCPS"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-purple-500"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Experience (Yrs)</label>
                <input 
                  type="number" 
                  v-model="newDoctor.experience_years" 
                  required 
                  min="0"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-purple-500"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Fee ($)</label>
                <input 
                  type="number" 
                  v-model="newDoctor.consultation_fee" 
                  required 
                  min="0"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-purple-500"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Clinic / Room</label>
                <input 
                  type="text" 
                  v-model="newDoctor.room_no" 
                  required 
                  placeholder="Room 304"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-purple-500"
                />
              </div>
            </div>

            <!-- Doctor Photograph (Upload or Select Faculty Preset) -->
            <div class="bg-purple-50/50 border border-purple-100 rounded-2xl p-3 sm:p-4 space-y-3">
              <label class="block text-xs font-bold text-slate-700 uppercase">
                Doctor Photograph / Faculty Avatar (Optional)
              </label>

              <div class="flex items-center gap-3 sm:gap-4">
                <div class="relative group flex-shrink-0">
                  <img 
                    :src="docAvatarPreview || '/assets/images/doc1.jpg'" 
                    class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl object-cover border-2 border-purple-500 shadow-sm bg-white"
                    alt="Doctor Preview"
                  />
                  <button 
                    v-if="docAvatarPreview"
                    type="button" 
                    @click="removeDocAvatar"
                    class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center text-xs shadow hover:bg-red-600 transition cursor-pointer">
                    <X class="w-3 h-3" />
                  </button>
                </div>

                <div class="flex-1 space-y-1">
                  <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-slate-200 hover:border-purple-500 text-xs font-semibold text-slate-700 cursor-pointer shadow-sm hover:shadow transition">
                    <Upload class="w-3.5 h-3.5 text-purple-600" />
                    <span>Upload Photo</span>
                    <input type="file" accept="image/*" class="hidden" @change="handleDocFileUpload" />
                  </label>
                  <p class="text-[10px] sm:text-[11px] text-slate-400">JPG, PNG or WEBP (Max 3MB)</p>
                </div>
              </div>

              <!-- Quick Doctor Presets -->
              <div>
                <span class="text-[11px] font-semibold text-slate-500 block mb-1.5">Or Choose A Faculty Avatar:</span>
                <div class="grid grid-cols-4 sm:grid-cols-7 gap-1.5 sm:gap-2">
                  <button 
                    v-for="p in docAvatarPresets" 
                    :key="p.id"
                    type="button"
                    @click="selectDocPreset(p)"
                    class="p-1 rounded-xl border flex flex-col items-center gap-1 transition cursor-pointer hover:bg-white"
                    :class="selectedDocPreset === p.id ? 'border-purple-600 bg-purple-100/60 ring-2 ring-purple-500/20' : 'border-slate-200 bg-white/70'">
                    <img :src="p.url" class="w-7 h-7 sm:w-9 sm:h-9 rounded-lg object-cover" />
                    <span class="text-[8px] sm:text-[9px] font-bold text-slate-600 truncate w-full text-center">{{ p.label }}</span>
                  </button>
                </div>
              </div>
            </div>

          </div>

          <!-- Sticky Footer -->
          <div class="p-3.5 sm:p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-2.5 sm:gap-3 flex-shrink-0">
            <button 
              type="button" 
              @click="isDoctorModalOpen = false"
              class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-white transition cursor-pointer">
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="docFormLoading"
              class="px-5 py-2 rounded-xl bg-purple-700 hover:bg-purple-800 text-white text-xs font-bold shadow-md transition cursor-pointer disabled:opacity-50">
              <span v-if="docFormLoading">Registering Doctor...</span>
              <span v-else>Register Doctor</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 2: ADD DEPARTMENT (Fully Responsive) -->
    <div v-if="isDeptModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-md p-2 sm:p-4 md:p-6 flex items-start sm:items-center justify-center">
      <div class="relative w-full max-w-md bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-100 my-2 sm:my-auto max-h-[calc(100dvh-1rem)] sm:max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95">
        <div class="p-3.5 sm:p-5 border-b border-slate-100 flex items-center justify-between flex-shrink-0 bg-white">
          <h3 class="text-sm sm:text-base font-bold text-slate-900">Add Clinical Department</h3>
          <button @click="isDeptModalOpen = false" class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitNewDepartment" class="flex flex-col flex-1 min-h-0 overflow-hidden">
          <div class="p-3.5 sm:p-6 overflow-y-auto flex-1 min-h-0 space-y-3 sm:space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Department Name</label>
              <input 
                type="text" 
                v-model="newDept.name" 
                required 
                placeholder="e.g. Oncology & Chemotherapy"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-purple-500"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Head Doctor Name</label>
              <input 
                type="text" 
                v-model="newDept.head_doctor_name" 
                placeholder="e.g. Dr. Arthur Miller, MD"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-purple-500"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Floor Location</label>
              <input 
                type="text" 
                v-model="newDept.room_floor" 
                placeholder="3rd Floor, West Wing"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-purple-500"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Department Description</label>
              <textarea 
                v-model="newDept.description" 
                required 
                rows="3"
                placeholder="Specialized comprehensive diagnostic and treatment services..."
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-purple-500"></textarea>
            </div>
          </div>

          <div class="p-3.5 sm:p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-2.5 sm:gap-3 flex-shrink-0">
            <button 
              type="button" 
              @click="isDeptModalOpen = false"
              class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-white transition cursor-pointer">
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="deptFormLoading"
              class="px-5 py-2 rounded-xl bg-purple-700 hover:bg-purple-800 text-white text-xs font-bold shadow transition cursor-pointer">
              <span v-if="deptFormLoading">Creating...</span>
              <span v-else>Create Department</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 3: ADD SERVICE (Fully Responsive) -->
    <div v-if="isServiceModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-md p-2 sm:p-4 md:p-6 flex items-start sm:items-center justify-center">
      <div class="relative w-full max-w-md bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-100 my-2 sm:my-auto max-h-[calc(100dvh-1rem)] sm:max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95">
        <div class="p-3.5 sm:p-5 border-b border-slate-100 flex items-center justify-between flex-shrink-0 bg-white">
          <h3 class="text-sm sm:text-base font-bold text-slate-900">Add Clinical Service</h3>
          <button @click="isServiceModalOpen = false" class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitNewService" class="flex flex-col flex-1 min-h-0 overflow-hidden">
          <div class="p-3.5 sm:p-6 overflow-y-auto flex-1 min-h-0 space-y-3 sm:space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Service Title</label>
              <input 
                type="text" 
                v-model="newService.title" 
                required 
                placeholder="e.g. 128-Slice Cardiac CT Angiography"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-purple-500"
              />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Starting Price ($)</label>
                <input 
                  type="number" 
                  v-model="newService.starting_price" 
                  required 
                  min="0"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-purple-500"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Department</label>
                <input 
                  type="text" 
                  v-model="newService.department_name" 
                  placeholder="Radiology"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-purple-500"
                />
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Description</label>
              <textarea 
                v-model="newService.description" 
                required 
                rows="3"
                placeholder="Comprehensive high-resolution imaging procedure..."
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-purple-500"></textarea>
            </div>
          </div>

          <div class="p-3.5 sm:p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-2.5 sm:gap-3 flex-shrink-0">
            <button 
              type="button" 
              @click="isServiceModalOpen = false"
              class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-white transition cursor-pointer">
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="serviceFormLoading"
              class="px-5 py-2 rounded-xl bg-purple-700 hover:bg-purple-800 text-white text-xs font-bold shadow transition cursor-pointer">
              <span v-if="serviceFormLoading">Saving...</span>
              <span v-else>Save Service</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 4: ADD EVENT / CAMP (Fully Responsive) -->
    <div v-if="isEventModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-md p-2 sm:p-4 md:p-6 flex items-start sm:items-center justify-center">
      <div class="relative w-full max-w-md bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-100 my-2 sm:my-auto max-h-[calc(100dvh-1rem)] sm:max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95">
        <div class="p-3.5 sm:p-5 border-b border-slate-100 flex items-center justify-between flex-shrink-0 bg-white">
          <h3 class="text-sm sm:text-base font-bold text-slate-900">Schedule Health Camp / Event</h3>
          <button @click="isEventModalOpen = false" class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitNewEvent" class="flex flex-col flex-1 min-h-0 overflow-hidden">
          <div class="p-3.5 sm:p-6 overflow-y-auto flex-1 min-h-0 space-y-3 sm:space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Event Title</label>
              <input 
                type="text" 
                v-model="newEvent.title" 
                required 
                placeholder="e.g. Free Diabetes & Hypertension Screening Camp"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-teal-500"
              />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Category</label>
                <select 
                  v-model="newEvent.category"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-teal-500">
                  <option value="Free Medical Camp">Free Medical Camp</option>
                  <option value="Blood Donation">Blood Donation</option>
                  <option value="Health Workshop">Health Workshop</option>
                  <option value="Screening Drive">Screening Drive</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Event Date</label>
                <input 
                  type="date" 
                  v-model="newEvent.event_date" 
                  required 
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-teal-500"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Time Slot</label>
                <input 
                  type="text" 
                  v-model="newEvent.time_slot" 
                  required 
                  placeholder="09:00 AM - 04:00 PM"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-teal-500"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Location</label>
                <input 
                  type="text" 
                  v-model="newEvent.location" 
                  required 
                  placeholder="Main Auditorium"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-teal-500"
                />
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Description</label>
              <textarea 
                v-model="newEvent.description" 
                required 
                rows="3"
                placeholder="Free consultation with senior cardiologists, free blood sugar tests..."
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-teal-500"></textarea>
            </div>
          </div>

          <div class="p-3.5 sm:p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-2.5 sm:gap-3 flex-shrink-0">
            <button 
              type="button" 
              @click="isEventModalOpen = false"
              class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-white transition cursor-pointer">
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="eventFormLoading"
              class="px-5 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow transition cursor-pointer">
              <span v-if="eventFormLoading">Scheduling...</span>
              <span v-else>Schedule Event</span>
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

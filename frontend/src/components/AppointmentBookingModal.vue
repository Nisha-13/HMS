<script setup>
import { ref, computed, watch } from 'vue';
import { useHospitalStore } from '../stores/hospitalStore';
import { useAuthStore } from '../stores/authStore';
import { jsPDF } from 'jspdf';
import { 
  X, Calendar, Clock, User, Phone, Mail, Stethoscope, 
  CheckCircle2, AlertCircle, FileText, Ticket, CreditCard,
  Building2, Activity, Sparkles, ShieldCheck, ArrowRight,
  AlertTriangle, DollarSign, Printer, Download
} from 'lucide-vue-next';


const props = defineProps({
  isOpen: Boolean,
  initialData: Object,
});

const emit = defineEmits(['close', 'booked']);

const hospitalStore = useHospitalStore();
const authStore = useAuthStore();

const activeTab = ref('doctor'); // 'doctor' | 'service'
const step = ref(1); // 1: Form, 2: Confirmation
const loading = ref(false);
const errorMsg = ref('');
const bookedResult = ref(null);

// Notice / Alert Popup State
const showNoticePopup = ref(false);
const noticeTitle = ref('Booking Notice');
const noticeMessage = ref('');
const noticeToken = ref('');

const getTodayString = () => {
  const d = new Date();
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const minDate = computed(() => getTodayString());

// Form State
const form = ref({
  department_id: '',
  doctor_id: '',
  service_id: '',
  service_name: '',
  facility_room: '',
  appointment_date: getTodayString(),
  time_slot: '10:30 AM',
  type: 'opd', // 'opd' | 'service'
  fee: 50.00,
  patient_name: authStore.user?.name || '',
  patient_phone: authStore.user?.phone || '',
  patient_email: authStore.user?.email || '',
  cnic: authStore.user?.patient_profile?.cnic || '',
  symptoms: '',
  patient_notes: '',
});

const timeSlots = [
  '09:00 AM', '09:30 AM', '10:00 AM', '10:30 AM', 
  '11:00 AM', '11:30 AM', '02:00 PM', '02:30 PM', 
  '03:00 PM', '03:30 PM', '04:00 PM', '04:30 PM'
];

// Handle incoming initialData props
watch(() => props.initialData, (newVal) => {
  if (newVal) {
    if (newVal.mode === 'service' || newVal.service) {
      activeTab.value = 'service';
      form.value.type = 'service';
      if (newVal.service) {
        selectService(newVal.service);
      }
    } else {
      activeTab.value = 'doctor';
      form.value.type = 'opd';
      if (newVal.department_id) form.value.department_id = newVal.department_id;
      if (newVal.doctor_id) form.value.doctor_id = newVal.doctor_id;
    }

    if (newVal.patient_name) form.value.patient_name = newVal.patient_name;
    if (newVal.patient_phone) form.value.patient_phone = newVal.patient_phone;
    if (newVal.cnic) form.value.cnic = newVal.cnic;
    if (newVal.appointment_date) {
      form.value.appointment_date = newVal.appointment_date >= minDate.value
        ? newVal.appointment_date
        : minDate.value;
    }
  }
}, { immediate: true });

// Filtered Doctors
const filteredDoctors = computed(() => {
  if (!form.value.department_id) return hospitalStore.doctors;
  return hospitalStore.doctors.filter(
    d => d.doctor_profile?.department_id == form.value.department_id
  );
});

const selectedDoctor = computed(() => {
  return hospitalStore.doctors.find(d => d.id == form.value.doctor_id);
});

const selectedService = computed(() => {
  if (!form.value.service_id) return hospitalStore.services[0] || null;
  return hospitalStore.services.find(s => s.id == form.value.service_id) || hospitalStore.services[0];
});

const selectService = (service) => {
  if (!service) return;
  form.value.service_id = service.id;
  form.value.service_name = service.title;
  form.value.facility_room = service.department_name || 'Specialized Diagnostic Suite';
  form.value.fee = service.starting_price > 0 ? service.starting_price : 75.00;
  form.value.type = 'service';

  // Auto match department
  const matchedDept = hospitalStore.departments.find(d => 
    service.department_name?.toLowerCase().includes(d.name.toLowerCase()) ||
    d.name.toLowerCase().includes(service.department_name?.toLowerCase())
  );
  if (matchedDept) {
    form.value.department_id = matchedDept.id;
    // pick first doctor in that dept
    const docInDept = hospitalStore.doctors.find(d => d.doctor_profile?.department_id == matchedDept.id);
    if (docInDept) form.value.doctor_id = docInDept.id;
  } else if (hospitalStore.doctors.length > 0) {
    form.value.doctor_id = hospitalStore.doctors[0].id;
  }
};

const switchTab = (tab) => {
  activeTab.value = tab;
  errorMsg.value = '';
  if (tab === 'service') {
    form.value.type = 'service';
    if (!form.value.service_id && hospitalStore.services.length > 0) {
      selectService(hospitalStore.services[0]);
    }
  } else {
    form.value.type = 'opd';
    form.value.service_name = '';
    form.value.facility_room = '';
    if (selectedDoctor.value?.doctor_profile) {
      form.value.fee = selectedDoctor.value.doctor_profile.consultation_fee;
    }
  }
};

const handleCnicInput = (e) => {
  let digits = e.target.value.replace(/\D/g, '').slice(0, 13);
  let formatted = digits;
  if (digits.length > 5 && digits.length <= 12) {
    formatted = `${digits.slice(0, 5)}-${digits.slice(5)}`;
  } else if (digits.length > 12) {
    formatted = `${digits.slice(0, 5)}-${digits.slice(5, 12)}-${digits.slice(12, 13)}`;
  }
  form.value.cnic = formatted;
};

const handlePhoneInput = (e) => {
  let digits = e.target.value.replace(/\D/g, '').slice(0, 11);
  let formatted = digits;
  if (digits.length > 4) {
    formatted = `${digits.slice(0, 4)}-${digits.slice(4)}`;
  }
  form.value.patient_phone = formatted;
};

const handleSubmit = async () => {
  loading.value = true;
  errorMsg.value = '';

  if (form.value.appointment_date && form.value.appointment_date < minDate.value) {
    const dateMsg = 'Preferred date cannot be before today.';
    errorMsg.value = dateMsg;
    noticeTitle.value = 'Invalid Date Selected';
    noticeMessage.value = dateMsg;
    noticeToken.value = '';
    showNoticePopup.value = true;
    loading.value = false;
    return;
  }

  // Pre-validate CNIC
  if (form.value.cnic) {
    const cnicDigits = form.value.cnic.replace(/\D/g, '');
    if (cnicDigits.length !== 13) {
      const msg = 'Please enter a complete 13-digit CNIC (e.g. 35201-1234567-1).';
      errorMsg.value = msg;
      noticeTitle.value = 'Incomplete CNIC';
      noticeMessage.value = msg;
      noticeToken.value = '';
      showNoticePopup.value = true;
      loading.value = false;
      return;
    }
    const firstD = parseInt(cnicDigits[0]);
    if (firstD < 1 || firstD > 7) {
      const msg = 'Invalid CNIC: First digit (province code) must be between 1 and 7.';
      errorMsg.value = msg;
      noticeTitle.value = 'Invalid Province Code';
      noticeMessage.value = msg;
      noticeToken.value = '';
      showNoticePopup.value = true;
      loading.value = false;
      return;
    }
  }

  // Pre-validate Phone
  if (form.value.patient_phone) {
    const phoneDigits = form.value.patient_phone.replace(/\D/g, '');
    if (phoneDigits.length !== 11 || !phoneDigits.startsWith('03')) {
      const msg = 'Please enter a valid 11-digit Pakistani mobile number starting with 03 (e.g. 0300-1234567).';
      errorMsg.value = msg;
      noticeTitle.value = 'Invalid Mobile Number';
      noticeMessage.value = msg;
      noticeToken.value = '';
      showNoticePopup.value = true;
      loading.value = false;
      return;
    }
  }

  // Fallback doctor assignment if booking service
  if (activeTab.value === 'service' && !form.value.doctor_id && hospitalStore.doctors.length > 0) {
    form.value.doctor_id = hospitalStore.doctors[0].id;
  }

  try {
    const payload = {
      ...form.value,
      type: activeTab.value === 'service' ? 'service' : 'opd',
      service_name: activeTab.value === 'service' ? (selectedService.value?.title || 'Clinical Diagnostic Service') : null,
      facility_room: activeTab.value === 'service' ? (selectedService.value?.department_name || 'Diagnostic Suite') : null,
      fee: activeTab.value === 'service' ? (selectedService.value?.starting_price || 75.00) : (selectedDoctor.value?.doctor_profile?.consultation_fee || 50.00)
    };

    const res = await hospitalStore.bookAppointment(payload);
    if (res.success) {
      bookedResult.value = res;
      step.value = 2;
      emit('booked', res);
    } else {
      const msg = res.message || 'Failed to complete booking.';
      errorMsg.value = msg;

      const tokenMatch = msg.match(/\(Token:\s*([^)]+)\)/i);
      noticeToken.value = tokenMatch ? tokenMatch[1] : '';

      if (msg.toLowerCase().includes('already booked') || msg.toLowerCase().includes('already exists') || msg.toLowerCase().includes('duplicate')) {
        noticeTitle.value = activeTab.value === 'service' ? 'Diagnostic Booking Already Exists' : 'Appointment Already Booked';
      } else {
        noticeTitle.value = activeTab.value === 'service' ? 'Diagnostics Notice' : 'Booking Notice';
      }
      noticeMessage.value = msg;
      showNoticePopup.value = true;
    }
  } catch (err) {
    const fallbackMsg = 'An unexpected error occurred. Please try again.';
    errorMsg.value = fallbackMsg;
    noticeTitle.value = 'Booking Error';
    noticeMessage.value = fallbackMsg;
    noticeToken.value = '';
    showNoticePopup.value = true;
  } finally {
    loading.value = false;
  }
};

const handlePrintSlip = () => {
  if (!bookedResult.value) return;

  const apt = bookedResult.value.appointment || {};
  const token = bookedResult.value.token_number || apt.token_no || 'T-000';
  const doctor = apt.doctor?.name || selectedDoctor.value?.name || 'Assigned Specialist';
  const department = apt.department?.name || selectedDoctor.value?.doctor_profile?.department?.name || 'General OPD';
  const counter = apt.opd_queue?.counter_no || apt.doctor?.doctor_profile?.room_no || 'Room 101';
  const date = apt.appointment_date || form.value.appointment_date;
  const time = apt.time_slot || form.value.time_slot;
  const fee = apt.fee || form.value.fee || 50;
  const patient = apt.patient?.name || form.value.patient_name;
  const cnic = apt.cnic || form.value.cnic || 'N/A';
  const phone = apt.patient?.phone || form.value.patient_phone || 'N/A';
  const aptNo = apt.appointment_no || 'N/A';

  const printWindow = window.open('', '_blank', 'width=420,height=650');
  if (!printWindow) {
    window.print();
    return;
  }

  const html = `<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>OPD Consultation Slip - ${token}</title>
  <style>
    @page { size: 80mm auto; margin: 4mm; }
    * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      margin: 0; padding: 12px; color: #0f172a; background: #ffffff; max-width: 360px; margin: 0 auto;
    }
    .header { text-align: center; border-bottom: 2px dashed #0284c7; padding-bottom: 8px; margin-bottom: 10px; }
    .hospital-title { font-size: 13px; font-weight: 800; color: #0369a1; text-transform: uppercase; letter-spacing: 0.5px; }
    .slip-title { font-size: 16px; font-weight: 900; color: #0f172a; margin: 4px 0 2px 0; }
    .slip-subtitle { font-size: 10px; color: #64748b; }
    .token-box { background: #f0f9ff; border: 2px solid #0284c7; border-radius: 12px; text-align: center; padding: 10px 8px; margin: 10px 0; }
    .token-label { font-size: 9px; font-weight: 800; color: #0369a1; text-transform: uppercase; letter-spacing: 1.5px; }
    .token-number { font-size: 42px; font-weight: 900; font-family: monospace; color: #0c4a6e; line-height: 1; margin: 4px 0; letter-spacing: 2px; }
    .counter-badge { font-size: 11px; font-weight: 700; color: #0369a1; }
    .info-table { width: 100%; border-collapse: collapse; font-size: 11px; margin: 10px 0; }
    .info-table td { padding: 3.5px 0; vertical-align: top; }
    .info-table .lbl { color: #64748b; width: 38%; font-weight: 500; }
    .info-table .val { color: #0f172a; font-weight: 700; text-align: right; }
    .fee-box { background: #fffbeb; border: 1px solid #f59e0b; border-radius: 8px; padding: 8px 12px; display: flex; justify-content: space-between; align-items: center; margin: 10px 0; }
    .fee-lbl { font-size: 11px; font-weight: 700; color: #b45309; }
    .fee-val { font-size: 16px; font-weight: 900; font-family: monospace; color: #92400e; }
    .footer { border-top: 1px dashed #cbd5e1; padding-top: 8px; margin-top: 10px; text-align: center; font-size: 9.5px; color: #64748b; line-height: 1.4; }
    .status-text { font-weight: 700; color: #0284c7; margin-top: 2px; }
    .meta-text { margin-top: 6px; font-size: 8px; color: #94a3b8; }
  </style>
</head>
<body>
  <div class="header">
    <div class="hospital-title">HealthPulse Hospital &amp; Research Center</div>
    <div class="slip-title">ONLINE APPOINTMENT RESERVATION SLIP</div>
    <div class="slip-subtitle">Central Reception Desk • Verification Pass</div>
  </div>

  <div class="token-box">
    <div class="token-label">Queue Token Number</div>
    <div class="token-number">${token}</div>
    <div class="counter-badge">Counter: ${counter}</div>
  </div>

  <table class="info-table">
    <tr><td class="lbl">Patient Name:</td><td class="val">${patient}</td></tr>
    <tr><td class="lbl">CNIC / ID:</td><td class="val" style="font-family: monospace;">${cnic}</td></tr>
    <tr><td class="lbl">Phone:</td><td class="val">${phone}</td></tr>
    <tr><td class="lbl">Doctor / Service:</td><td class="val">${doctor}</td></tr>
    <tr><td class="lbl">Department:</td><td class="val" style="color: #0284c7;">${department}</td></tr>
    <tr><td class="lbl">Scheduled Slot:</td><td class="val">${date} · ${time}</td></tr>
    <tr><td class="lbl">Booking Ref:</td><td class="val" style="font-family: monospace;">${aptNo}</td></tr>
  </table>

  <div class="fee-box">
    <span class="fee-lbl">Fee Due at Reception (Cash):</span>
    <span class="fee-val">$${fee}</span>
  </div>

  <div class="footer">
    <div>Please arrive 10-15 minutes prior and present this Token at Central Reception.</div>
    <div class="status-text">Your slot and token have been reserved.</div>
    <div class="meta-text">Booked online on ${new Date().toLocaleString()} | HealthPulse HMS</div>
  </div>
</body>
</html>`;

  printWindow.document.open();
  printWindow.document.write(html);
  printWindow.document.close();

  setTimeout(() => {
    try {
      printWindow.focus();
      printWindow.print();
      printWindow.close();
    } catch (e) {
      console.error('Print window error:', e);
    }
  }, 400);
};

const handleDownloadSlipPdf = () => {
  if (!bookedResult.value) return;

  const apt = bookedResult.value.appointment || {};
  const token = bookedResult.value.token_number || apt.token_no || 'T-000';
  const doctor = apt.doctor?.name || selectedDoctor.value?.name || 'Assigned Specialist';
  const department = apt.department?.name || selectedDoctor.value?.doctor_profile?.department?.name || 'General OPD';
  const counter = apt.opd_queue?.counter_no || apt.doctor?.doctor_profile?.room_no || 'Room 101';
  const date = apt.appointment_date || form.value.appointment_date;
  const time = apt.time_slot || form.value.time_slot;
  const fee = apt.fee || form.value.fee || 50;
  const patient = apt.patient?.name || form.value.patient_name;
  const cnic = apt.cnic || form.value.cnic || 'N/A';
  const phone = apt.patient?.phone || form.value.patient_phone || 'N/A';
  const aptNo = apt.appointment_no || 'N/A';

  const doc = new jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: [100, 165]
  });

  const pageW = 100;
  let y = 10;

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8);
  doc.setTextColor(3, 105, 161);
  doc.text('HEALTHPULSE HOSPITAL & RESEARCH CENTER', pageW / 2, y, { align: 'center' });
  y += 5;

  doc.setFontSize(12);
  doc.setTextColor(15, 23, 42);
  doc.text('APPOINTMENT RESERVATION SLIP', pageW / 2, y, { align: 'center' });
  y += 4;

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(7);
  doc.setTextColor(100, 116, 139);
  doc.text('Present this slip at Central Reception to pay fee & check in', pageW / 2, y, { align: 'center' });
  y += 6;

  // Token Box
  doc.setFillColor(240, 249, 255);
  doc.setDrawColor(2, 132, 199);
  doc.setLineWidth(0.5);
  doc.roundedRect(8, y, pageW - 16, 26, 3, 3, 'FD');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(7);
  doc.setTextColor(3, 105, 161);
  doc.text('RESERVED TOKEN NUMBER', pageW / 2, y + 5, { align: 'center' });

  doc.setFontSize(26);
  doc.setTextColor(12, 74, 110);
  doc.text(String(token), pageW / 2, y + 17, { align: 'center' });

  doc.setFontSize(8);
  doc.setTextColor(3, 105, 161);
  doc.text(`Counter: ${counter}`, pageW / 2, y + 23, { align: 'center' });
  y += 31;

  const drawRow = (label, val, isBold = false) => {
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.5);
    doc.setTextColor(100, 116, 139);
    doc.text(label, 10, y);

    doc.setFont('helvetica', isBold ? 'bold' : 'normal');
    doc.setTextColor(15, 23, 42);
    doc.text(String(val || 'N/A'), pageW - 10, y, { align: 'right' });
    y += 5.5;
  };

  drawRow('Patient Name:', patient, true);
  drawRow('CNIC / ID:', cnic);
  drawRow('Contact Phone:', phone);
  drawRow('Doctor / Service:', doctor, true);
  drawRow('Department:', department);
  drawRow('Scheduled Slot:', `${date || ''} · ${time || ''}`);
  drawRow('Booking Ref:', aptNo);
  y += 2;

  // Fee Box
  doc.setFillColor(254, 252, 232); // amber-50
  doc.setDrawColor(245, 158, 11); // amber-500
  doc.setLineWidth(0.4);
  doc.roundedRect(8, y, pageW - 16, 12, 2, 2, 'FD');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8);
  doc.setTextColor(180, 83, 9);
  doc.text('Fee Due at Reception (Cash):', 12, y + 7.5);

  doc.setFontSize(11);
  doc.setTextColor(146, 64, 14);
  doc.text(`$${fee}`, pageW - 12, y + 7.5, { align: 'right' });
  y += 17;

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(6.5);
  doc.setTextColor(100, 116, 139);
  doc.text('Please arrive 10-15 minutes early and show this token to check in.', pageW / 2, y, { align: 'center' });
  y += 5;

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(6);
  doc.setTextColor(148, 163, 184);
  doc.text(`Booked on: ${new Date().toLocaleString()} | HealthPulse HMS`, pageW / 2, y, { align: 'center' });

  doc.save(`HealthPulse_Reservation_Slip_${token}.pdf`);
};

const handleClose = () => {
  step.value = 1;
  bookedResult.value = null;
  emit('close');
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-md p-2 sm:p-4 md:p-6 flex items-start sm:items-center justify-center">
    <div class="relative w-full max-w-2xl bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-100 my-2 sm:my-auto max-h-[calc(100dvh-1rem)] sm:max-h-[92vh] flex flex-col overflow-hidden transform transition-all animate-in fade-in zoom-in-95">
      
      <!-- Header with Dual Mode Switcher -->
      <div class="bg-gradient-to-r from-brand-900 via-brand-800 to-brand-700 text-white p-4 sm:p-6 relative overflow-hidden flex-shrink-0">
        <div class="flex justify-between items-start mb-3 sm:mb-4">
          <div class="flex items-center gap-2.5 sm:gap-3">
            <div class="p-2 sm:p-2.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/10">
              <Activity v-if="activeTab === 'service'" class="w-5 h-5 sm:w-6 sm:h-6 text-brand-300" />
              <Calendar v-else class="w-5 h-5 sm:w-6 sm:h-6 text-brand-200" />
            </div>
            <div>
              <h2 class="text-base sm:text-xl font-bold leading-tight">
                {{ activeTab === 'service' ? 'Schedule Clinical Service & Diagnostics' : 'Book Specialist OPD Consultation' }}
              </h2>
              <p class="text-[11px] sm:text-xs text-brand-100 mt-0.5">HealthPulse Smart Medical Booking System</p>
            </div>
          </div>
          <button @click="handleClose" class="p-1.5 sm:p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-full transition-colors cursor-pointer">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Mode Toggle Tabs -->
        <div v-if="step === 1" class="flex p-1 bg-black/20 backdrop-blur-md rounded-2xl border border-white/10">
          <button 
            type="button"
            @click="switchTab('doctor')"
            :class="[
              'flex-1 py-2 px-2 sm:px-3 text-[11px] sm:text-xs font-bold rounded-xl flex items-center justify-center gap-1.5 sm:gap-2 transition-all cursor-pointer',
              activeTab === 'doctor' ? 'bg-white text-slate-900 shadow-md' : 'text-brand-100 hover:text-white hover:bg-white/5'
            ]">
            <Stethoscope class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
            <span>Doctor OPD</span>
          </button>
          <button 
            type="button"
            @click="switchTab('service')"
            :class="[
              'flex-1 py-2 px-2 sm:px-3 text-[11px] sm:text-xs font-bold rounded-xl flex items-center justify-center gap-1.5 sm:gap-2 transition-all cursor-pointer',
              activeTab === 'service' ? 'bg-white text-slate-900 shadow-md' : 'text-brand-100 hover:text-white hover:bg-white/5'
            ]">
            <Building2 class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
            <span>Diagnostics & Labs</span>
          </button>
        </div>
      </div>

      <!-- Step 1: Booking Form (Smoothly Scrollable on Any Screen) -->
      <div v-if="step === 1" class="p-4 sm:p-7 overflow-y-auto flex-1 min-h-0">
        <div v-if="errorMsg" class="mb-5 p-3.5 sm:p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
          <AlertCircle class="w-4 h-4 flex-shrink-0" />
          <span>{{ errorMsg }}</span>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-5">
          
          <!-- TAB 1: DOCTOR CONSULTATION FIELDS -->
          <div v-if="activeTab === 'doctor'" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                  Department
                </label>
                <select 
                  v-model="form.department_id"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                  <option value="">All Departments</option>
                  <option v-for="d in hospitalStore.departments" :key="d.id" :value="d.id">
                    {{ d.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                  Specialist Doctor <span class="text-red-500">*</span>
                </label>
                <select 
                  v-model="form.doctor_id"
                  required
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                  <option value="" disabled>Choose Doctor</option>
                  <option v-for="doc in filteredDoctors" :key="doc.id" :value="doc.id">
                    {{ doc.name }} ({{ doc.doctor_profile?.specialty }})
                  </option>
                </select>
              </div>
            </div>

            <!-- Doctor Profile Badge -->
            <div v-if="selectedDoctor" class="p-4 rounded-2xl bg-brand-50/80 border border-brand-100 flex items-center justify-between">
              <div class="flex items-center gap-3.5">
                <img :src="'/' + (selectedDoctor.avatar || 'assets/images/doc1.jpg')" class="w-12 h-12 rounded-xl object-cover border border-brand-200 shadow-sm" />
                <div>
                  <h4 class="text-sm font-bold text-slate-900">{{ selectedDoctor.name }}</h4>
                  <p class="text-xs text-brand-700 font-semibold">{{ selectedDoctor.doctor_profile?.qualification }}</p>
                  <p class="text-[11px] text-slate-500 mt-0.5">{{ selectedDoctor.doctor_profile?.room_no }} • {{ selectedDoctor.doctor_profile?.available_days }}</p>
                </div>
              </div>
              <div class="text-right">
                <span class="text-[11px] font-semibold text-slate-500 block">Consultation Fee</span>
                <span class="text-lg font-extrabold text-brand-600">${{ selectedDoctor.doctor_profile?.consultation_fee }}</span>
              </div>
            </div>
          </div>

          <!-- TAB 2: DIAGNOSTIC & CLINICAL SERVICE FIELDS -->
          <div v-else class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                Select Medical Procedure / Facility <span class="text-red-500">*</span>
              </label>
              <select 
                v-model="form.service_id"
                @change="selectService(hospitalStore.services.find(s => s.id == form.service_id))"
                required
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 font-medium">
                <option v-for="s in hospitalStore.services" :key="s.id" :value="s.id">
                  {{ s.title }} ({{ s.department_name }})
                </option>
              </select>
            </div>

            <!-- Service Details Showcase -->
            <div v-if="selectedService" class="p-4 rounded-2xl bg-slate-900 text-white border border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
              <div class="flex items-center gap-3.5">
                <img :src="'/' + (selectedService.image || 'assets/images/service1.jpg')" class="w-16 h-16 rounded-2xl object-cover border border-slate-700 shadow-md" />
                <div>
                  <span class="text-[10px] font-bold text-brand-300 bg-brand-950/80 px-2 py-0.5 rounded-full border border-brand-800">
                    {{ selectedService.department_name }}
                  </span>
                  <h4 class="text-sm font-bold text-white mt-1">{{ selectedService.title }}</h4>
                  <p class="text-[11px] text-slate-400 line-clamp-1 mt-0.5">{{ selectedService.description }}</p>
                </div>
              </div>
              <div class="text-left sm:text-right shrink-0">
                <span class="text-[11px] text-slate-400 block font-medium">Estimated Pricing</span>
                <span class="text-lg font-extrabold text-emerald-400">
                  {{ selectedService.starting_price > 0 ? '$' + selectedService.starting_price : '24/7 Available' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Date & Time Slot (Common) -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                Preferred Date <span class="text-red-500">*</span>
              </label>
              <input 
                type="date" 
                :min="minDate"
                v-model="form.appointment_date"
                required
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                Time Slot <span class="text-red-500">*</span>
              </label>
              <select 
                v-model="form.time_slot"
                required
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option v-for="slot in timeSlots" :key="slot" :value="slot">
                  {{ slot }}
                </option>
              </select>
            </div>
          </div>

          <!-- Patient Information -->
          <div class="border-t border-slate-100 pt-4 space-y-3.5">
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Patient Details</h4>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                <div class="relative">
                  <input 
                    type="text" 
                    v-model="form.patient_name"
                    required
                    placeholder="Patient Full Name"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500"
                  />
                  <User class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between mb-1">
                  <label class="block text-xs font-semibold text-slate-700">
                    CNIC / National ID <span class="text-red-500">*</span>
                  </label>
                  <span class="text-[10px] text-brand-600 font-mono font-bold bg-brand-50 px-1.5 py-0.5 rounded">13-digit NADRA</span>
                </div>
                <div class="relative">
                  <input 
                    type="text" 
                    :value="form.cnic"
                    @input="handleCnicInput"
                    required
                    maxlength="15"
                    placeholder="35201-1234567-1"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2 text-sm font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500"
                  />
                  <CreditCard class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between mb-1">
                  <label class="block text-xs font-semibold text-slate-700">
                    Mobile Phone <span class="text-red-500">*</span>
                  </label>
                  <span class="text-[10px] text-slate-400 font-mono">03XX-XXXXXXX</span>
                </div>
                <div class="relative">
                  <input 
                    type="tel" 
                    :value="form.patient_phone"
                    @input="handlePhoneInput"
                    required
                    maxlength="12"
                    placeholder="0300-1234567"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2 text-sm font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500"
                  />
                  <Phone class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
                </div>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                <div class="relative">
                  <input 
                    type="email" 
                    v-model="form.patient_email"
                    placeholder="name@example.com"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500"
                  />
                  <Mail class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
                </div>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">
                {{ activeTab === 'service' ? 'Clinical Indication / Physician Notes' : 'Symptoms / Reason for Consultation' }}
              </label>
              <textarea 
                v-model="form.symptoms"
                rows="2"
                :placeholder="activeTab === 'service' ? 'Mention test requirements, physician referral, or specific diagnostic area...' : 'Briefly describe your symptoms or reason for visit...'"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-sm"
              ></textarea>
            </div>

            <!-- Authentic Hospital Reception Desk Payment Info Card -->
            <div class="bg-gradient-to-r from-slate-50 to-emerald-50/40 border border-emerald-200/80 rounded-2xl p-4 space-y-2.5">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-sm">
                    <DollarSign class="w-4 h-4" />
                  </div>
                  <div>
                    <span class="text-xs font-bold text-slate-900 block">Pay at Hospital Reception Desk</span>
                    <span class="text-[11px] text-emerald-700 font-medium">Cash on Arrival • Fast Express Check-In</span>
                  </div>
                </div>
                <div class="text-right">
                  <span class="text-xs text-slate-400 block font-medium">Consultation Fee</span>
                  <span class="text-sm font-extrabold text-slate-900 font-mono">
                    ${{ activeTab === 'service' ? (selectedService?.starting_price || 75.00) : (selectedDoctor?.doctor_profile?.consultation_fee || 50.00) }}
                  </span>
                </div>
              </div>
              <p class="text-[11px] text-slate-600 leading-relaxed bg-white/80 p-2.5 rounded-xl border border-slate-200/60">
                🏥 Your time slot and queue token are reserved immediately. Upon arrival at the hospital, present your Token or CNIC at the Central Reception Desk to settle fee and enter the waiting hall.
              </p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <span class="text-xs text-slate-500 flex items-center gap-1">
              <ShieldCheck class="w-4 h-4 text-emerald-600" /> Instant Token & Booking
            </span>
            <div class="flex items-center gap-3">
              <button 
                type="button" 
                @click="handleClose"
                class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold">
                Cancel
              </button>
              <button 
                type="submit" 
                :disabled="loading"
                class="flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold uppercase tracking-wider py-3 px-6 rounded-xl shadow-lg shadow-brand-500/25 disabled:opacity-50 transition-all">
                <span v-if="loading">Processing...</span>
                <span v-else>
                  {{ activeTab === 'service' ? 'Confirm Service & Get Slip →' : 'Confirm Doctor Token →' }}
                </span>
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Step 2: Confirmation Token & Slip (Responsive & Scrollable) -->
      <div v-else-if="step === 2 && bookedResult" class="p-5 sm:p-8 overflow-y-auto flex-1 min-h-0 text-center space-y-5">
        <div class="w-14 h-14 sm:w-16 sm:h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto shadow-inner animate-in zoom-in-50">
          <CheckCircle2 class="w-10 h-10" />
        </div>

        <div>
          <h3 class="text-2xl font-extrabold text-slate-900">
            {{ activeTab === 'service' ? 'Clinical Procedure Scheduled!' : 'Doctor Appointment Confirmed!' }}
          </h3>
          <p class="text-sm text-slate-500 mt-1">
            {{ activeTab === 'service' ? 'Your diagnostic/clinical facility slip is ready.' : 'Your OPD queue token is reserved.' }}
          </p>
        </div>

        <!-- Token Display Card -->
        <div class="bg-gradient-to-br from-brand-950 via-brand-900 to-brand-800 text-white rounded-3xl p-6 shadow-2xl max-w-md mx-auto text-left relative overflow-hidden border border-brand-700/50">
          <div class="flex justify-between items-start border-b border-brand-700/60 pb-3 mb-4">
            <div>
              <span class="text-[10px] uppercase font-extrabold tracking-widest text-brand-300">HealthPulse Hospital</span>
              <h4 class="text-base font-bold text-white">
                {{ activeTab === 'service' ? 'Diagnostic & Procedure Slip' : 'OPD Consultation Token' }}
              </h4>
            </div>
            <div class="px-3 py-1 bg-emerald-500/20 border border-emerald-400/50 text-emerald-300 text-xs font-bold rounded-lg flex items-center gap-1">
              <CheckCircle2 class="w-3.5 h-3.5" /> RESERVED
            </div>
          </div>

          <div class="flex items-center justify-between my-4 bg-white/10 p-4 rounded-2xl border border-white/10 backdrop-blur-md">
            <div>
              <span class="text-xs text-brand-200 block">Queue Token No</span>
              <span class="text-3xl font-extrabold text-white tracking-wider font-mono">
                {{ bookedResult.token_number || bookedResult.appointment?.token_no }}
              </span>
            </div>
            <div class="text-right">
              <span class="text-xs text-brand-200 block">Booking Reference</span>
              <span class="text-sm font-bold text-brand-300 font-mono">
                {{ bookedResult.appointment?.appointment_no }}
              </span>
            </div>
          </div>

          <div class="space-y-2 text-xs text-brand-100 pt-2 border-t border-brand-700/60">
            <div v-if="activeTab === 'service'">
              <p><strong>Procedure / Service:</strong> {{ bookedResult.appointment?.symptoms || form.service_name || 'Clinical Facility' }}</p>
              <p><strong>Facility Room:</strong> {{ bookedResult.appointment?.opd_queue?.counter_no || form.facility_room || 'Main Diagnostics' }}</p>
            </div>
            <div v-else>
              <p><strong>Specialist Doctor:</strong> {{ bookedResult.appointment?.doctor?.name }}</p>
              <p><strong>OPD Counter:</strong> {{ bookedResult.appointment?.doctor?.doctor_profile?.room_no || 'OPD Room 101' }}</p>
            </div>
            <p><strong>Scheduled Slot:</strong> {{ bookedResult.appointment?.appointment_date }} at {{ bookedResult.appointment?.time_slot }}</p>
            <p><strong>Patient Name:</strong> {{ bookedResult.appointment?.patient?.name || form.patient_name }}</p>
            <p v-if="bookedResult.appointment?.cnic || form.cnic"><strong>CNIC / National ID:</strong> {{ bookedResult.appointment?.cnic || form.cnic }}</p>
            <p v-if="bookedResult.appointment?.patient?.patient_profile?.mrn"><strong>Patient MRN:</strong> {{ bookedResult.appointment?.patient?.patient_profile?.mrn }}</p>
          </div>

          <!-- Reception Check-In & Fee Notice -->
          <div class="mt-4 p-3 bg-amber-500/20 border border-amber-400/40 rounded-2xl text-xs space-y-1 text-amber-200">
            <div class="flex items-center justify-between font-bold text-white">
              <span>Pay at Reception Desk:</span>
              <span class="text-emerald-300 font-mono text-sm">${{ bookedResult.appointment?.fee || form.fee }}</span>
            </div>
            <p class="text-[11px] text-amber-100/90 leading-tight">
              Please arrive 10-15 minutes early and show your Token #{{ bookedResult.token_number || bookedResult.appointment?.token_no }} at the Central Reception Desk to check in.
            </p>
          </div>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-3 pt-3">
          <button 
            type="button"
            @click="handlePrintSlip"
            class="flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider py-3 px-5 rounded-xl shadow-md transition-all cursor-pointer">
            <Printer class="w-3.5 h-3.5" /> Print Slip
          </button>
          <button 
            type="button"
            @click="handleDownloadSlipPdf"
            class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider py-3 px-5 rounded-xl shadow-md transition-all cursor-pointer">
            <Download class="w-3.5 h-3.5" /> Download PDF
          </button>
          <button 
            type="button"
            @click="handleClose"
            class="bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs uppercase tracking-wider py-3 px-7 rounded-xl shadow-lg shadow-brand-500/25 transition-all cursor-pointer">
            Done & Continue
          </button>
        </div>
      </div>

    </div>
  </div>

  <!-- High Priority Notice / Alert Modal Popup -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div 
        v-if="showNoticePopup" 
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm"
        @click.self="showNoticePopup = false"
      >
        <div 
          class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden p-6 sm:p-7 text-center transform transition-all"
          @click.stop
        >
          <!-- Decorative Top Gradient Line -->
          <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-amber-500 via-rose-500 to-brand-500"></div>

          <!-- Close Button -->
          <button 
            type="button"
            @click="showNoticePopup = false"
            class="absolute top-3.5 right-3.5 p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-full transition-colors cursor-pointer"
          >
            <X class="w-5 h-5" />
          </button>

          <!-- Warning Icon Badge with Pulsing Glow -->
          <div class="mx-auto w-16 h-16 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 mb-4 relative shadow-sm">
            <span class="absolute inset-0 rounded-2xl bg-amber-400/20 animate-ping opacity-70"></span>
            <AlertTriangle class="w-8 h-8 relative z-10 text-amber-600" />
          </div>

          <!-- Modal Heading -->
          <h3 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight mb-2">
            {{ noticeTitle }}
          </h3>

          <!-- Active Token Pill if Available -->
          <div v-if="noticeToken" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-amber-100/80 border border-amber-300/80 text-amber-900 font-mono font-bold text-xs mb-3 shadow-xs">
            <Ticket class="w-3.5 h-3.5 text-amber-700" />
            <span>Active Token: {{ noticeToken }}</span>
          </div>

          <!-- Message Body -->
          <p class="text-sm text-slate-600 leading-relaxed mb-6 px-1">
            {{ noticeMessage }}
          </p>

          <!-- Primary Action Button -->
          <button
            type="button"
            @click="showNoticePopup = false"
            class="w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-brand-700 to-brand-600 hover:from-brand-800 hover:to-brand-700 text-white font-bold text-sm shadow-lg shadow-brand-700/25 transition-all transform active:scale-95 cursor-pointer"
          >
            Understood / Theek Hai
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

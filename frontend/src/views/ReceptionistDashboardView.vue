<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import api from '../services/api';
import { jsPDF } from 'jspdf';
import { 
  Building, Ticket, BedDouble, UserCheck, Plus, 
  CheckCircle2, AlertCircle, RefreshCw, LogOut, Tv,
  Search, Calendar, Phone, Clock, User, HeartPulse,
  Activity, ShieldCheck, Printer, Download, ArrowRight, Sparkles,
  ChevronLeft, ChevronRight, DollarSign, Check, X
} from 'lucide-vue-next';


const wards = ref([]);
const appointments = ref([]);
const doctors = ref([]);
const eventRegistrations = ref([]);
const loading = ref(true);
const activeTab = ref('walkin'); // 'walkin' | 'appointments' | 'wards' | 'camps'
const searchQuery = ref('');
const generateLoading = ref(false);
const generateError = ref('');

// Walk-in Token form
const walkInForm = ref({
  patient_name: '',
  patient_phone: '',
  doctor_id: '',
  department_id: '',
});
const tokenResult = ref(null);
const tokenSuccessMessage = ref('');

// Walk-in form field-level validation errors
const walkInErrors = ref({
  patient_name: '',
  patient_phone: '',
  doctor_id: '',
});

// Auto-format Pakistani mobile: 03XX-XXXXXXX
const handleWalkInPhoneInput = (e) => {
  let digits = e.target.value.replace(/\D/g, '').slice(0, 11);
  let formatted = digits;
  if (digits.length > 4) {
    formatted = `${digits.slice(0, 4)}-${digits.slice(4)}`;
  }
  walkInForm.value.patient_phone = formatted;
  // Clear error while typing
  if (walkInErrors.value.patient_phone) walkInErrors.value.patient_phone = '';
};

// Validate walk-in form and return true if valid
const validateWalkInForm = () => {
  let valid = true;
  walkInErrors.value = { patient_name: '', patient_phone: '', doctor_id: '' };

  const name = walkInForm.value.patient_name.trim();
  if (!name) {
    walkInErrors.value.patient_name = 'Patient full name is required.';
    valid = false;
  } else if (name.length < 3) {
    walkInErrors.value.patient_name = 'Name must be at least 3 characters.';
    valid = false;
  } else if (/^\d+$/.test(name)) {
    walkInErrors.value.patient_name = 'Name cannot be all numbers. Enter a valid full name.';
    valid = false;
  } else if (!/^[a-zA-Z\s.'-]+$/.test(name)) {
    walkInErrors.value.patient_name = 'Name should contain letters only (no special characters or digits).';
    valid = false;
  }

  const phone = walkInForm.value.patient_phone.trim();
  if (phone) {
    const digits = phone.replace(/\D/g, '');
    if (digits.length !== 11) {
      walkInErrors.value.patient_phone = 'Phone must be 11 digits (e.g. 0300-1234567).';
      valid = false;
    } else if (!digits.startsWith('03')) {
      walkInErrors.value.patient_phone = 'Pakistani mobile numbers must start with 03 (e.g. 0300-1234567).';
      valid = false;
    }
  }

  if (!walkInForm.value.doctor_id) {
    walkInErrors.value.doctor_id = 'Please select an assigned doctor.';
    valid = false;
  }

  return valid;
};

// Bed Admission modal
const admitModalOpen = ref(false);
const selectedBed = ref(null);
const admitPatientName = ref('');
const admitDiagnosis = ref('');
const bedActionLoading = ref(false);

const fetchReceptionData = async () => {
  loading.value = true;
  generateError.value = '';
  try {
    const [resWards, resApts, resDocs, resRegs] = await Promise.all([
      api.get('/wards'),
      api.get('/appointments'),
      api.get('/doctors'),
      api.get('/events/registrations')
    ]);
    if (resWards.data.success) wards.value = resWards.data.wards || [];
    if (resApts.data.success) appointments.value = resApts.data.appointments || [];
    if (resDocs.data.success) doctors.value = resDocs.data.doctors || [];
    if (resRegs.data.success) eventRegistrations.value = resRegs.data.registrations || [];
  } catch (err) {
    console.error('Error loading reception data:', err);
  } finally {
    loading.value = false;
  }
};

const handleGenerateToken = async () => {
  // Run full client-side validation before submitting
  if (!validateWalkInForm()) return;

  generateLoading.value = true;
  generateError.value = '';
  try {
    const res = await api.post('/queue/generate-token', {
      patient_name: walkInForm.value.patient_name.trim(),
      patient_phone: walkInForm.value.patient_phone.trim() || null,
      doctor_id: walkInForm.value.doctor_id,
      department_id: walkInForm.value.department_id || null,
    });
    if (res.data.success) {
      tokenResult.value = res.data.token;
      tokenSuccessMessage.value = res.data.message;
      walkInForm.value = {
        patient_name: '',
        patient_phone: '',
        doctor_id: '',
        department_id: '',
      };
      walkInErrors.value = { patient_name: '', patient_phone: '', doctor_id: '' };
      await fetchReceptionData();
    }
  } catch (err) {
    console.error('Token generation error:', err);
    generateError.value = err.response?.data?.message || 'Failed to generate token. Please check inputs.';
  } finally {
    generateLoading.value = false;
  }
};

const openAdmit = (bed) => {
  selectedBed.value = bed;
  admitPatientName.value = '';
  admitDiagnosis.value = '';
  admitModalOpen.value = true;
};

const handleAdmit = async () => {
  if (!selectedBed.value || !admitPatientName.value) return;
  bedActionLoading.value = true;
  try {
    const res = await api.post('/wards/admit', {
      bed_id: selectedBed.value.id,
      patient_name: admitPatientName.value,
      notes: admitDiagnosis.value || 'Admitted via Central Reception Desk'
    });
    if (res.data.success) {
      admitModalOpen.value = false;
      await fetchReceptionData();
    }
  } catch (err) {
    console.error('Bed admission error:', err);
  } finally {
    bedActionLoading.value = false;
  }
};

const handleDischarge = async (bed) => {
  if (!confirm(`Are you sure you want to discharge patient from Bed #${bed.bed_number}?`)) return;
  try {
    const res = await api.post(`/beds/${bed.id}/discharge`);
    if (res.data.success) {
      await fetchReceptionData();
    }
  } catch (err) {
    console.error('Bed discharge error:', err);
  }
};

// Camp Registrations State & Actions
const campSearchQuery = ref('');
const campStatusFilter = ref('all'); // 'all' | 'confirmed' | 'completed'
const campUpdatingId = ref(null);
const campSuccessNotice = ref('');

const filteredCampRegistrations = computed(() => {
  let list = eventRegistrations.value;
  if (campStatusFilter.value !== 'all') {
    list = list.filter(r => (r.status || 'confirmed').toLowerCase() === campStatusFilter.value);
  }
  if (!campSearchQuery.value.trim()) return list;
  const q = campSearchQuery.value.toLowerCase();
  return list.filter(r => 
    (r.pass_code || '').toLowerCase().includes(q) ||
    (r.attendee_name || '').toLowerCase().includes(q) ||
    (r.cnic_id || '').toLowerCase().includes(q) ||
    (r.attendee_phone || '').toLowerCase().includes(q) ||
    (r.event?.title || '').toLowerCase().includes(q)
  );
});

const handleUpdateCampStatus = async (reg, newStatus) => {
  campUpdatingId.value = reg.id;
  campSuccessNotice.value = '';
  try {
    const res = await api.patch(`/events/registrations/${reg.id}/status`, {
      status: newStatus
    });
    if (res.data.success) {
      reg.status = newStatus;
      campSuccessNotice.value = `Pass #${reg.pass_code} marked as ${newStatus.toUpperCase()} successfully!`;
      setTimeout(() => { campSuccessNotice.value = ''; }, 4000);
    }
  } catch (err) {
    console.error('Error updating registration status:', err);
  } finally {
    campUpdatingId.value = null;
  }
};

// Fast Check-In State & Actions
const checkInUpdatingId = ref(null);
const checkInSuccessNotice = ref('');
const checkedInSlipData = ref(null);

const handleCheckIn = async (apt) => {
  checkInUpdatingId.value = apt.id;
  checkInSuccessNotice.value = '';
  try {
    const res = await api.post(`/appointments/${apt.id}/check-in`, {
      payment_method: 'cash'
    });
    if (res.data.success) {
      apt.payment_status = 'paid';
      apt.status = 'confirmed';
      if (apt.opd_queue) {
        apt.opd_queue.status = 'waiting';
      }
      checkInSuccessNotice.value = `Patient ${apt.patient?.name} (Token #${apt.token_no}) checked in! Consultation fee of $${apt.fee} collected in cash.`;
      checkedInSlipData.value = {
        token: apt.token_no,
        patientName: apt.patient?.name,
        doctorName: apt.doctor?.name,
        departmentName: apt.department?.name || 'General OPD',
        counter: apt.opd_queue?.counter_no || apt.doctor?.doctor_profile?.room_no || 'Room 101',
        fee: apt.fee,
        date: apt.appointment_date,
        time: apt.time_slot,
        appointmentNo: apt.appointment_no,
        cnic: apt.cnic || apt.patient?.patient_profile?.cnic || 'N/A',
        phone: apt.patient?.phone || 'N/A',
      };
      await fetchReceptionData();
    }
  } catch (err) {
    console.error('Check-in error:', err);
    alert(err.response?.data?.message || 'Failed to check in patient. Please try again.');
  } finally {
    checkInUpdatingId.value = null;
  }
};

// OPD Entry Slip Printing & PDF Download
const handlePrintSlip = (data) => {
  const slip = data || checkedInSlipData.value;
  if (!slip) return;

  const printWindow = window.open('', '_blank', 'width=420,height=650');
  if (!printWindow) {
    window.print();
    return;
  }

  const html = `<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>OPD Consultation Slip - ${slip.token || ''}</title>
  <style>
    @page { size: 80mm auto; margin: 4mm; }
    * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      margin: 0;
      padding: 12px;
      color: #0f172a;
      background: #ffffff;
      max-width: 360px;
      margin: 0 auto;
    }
    .header {
      text-align: center;
      border-bottom: 2px dashed #0284c7;
      padding-bottom: 8px;
      margin-bottom: 10px;
    }
    .hospital-title {
      font-size: 13px;
      font-weight: 800;
      color: #0369a1;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .slip-title {
      font-size: 16px;
      font-weight: 900;
      color: #0f172a;
      margin: 4px 0 2px 0;
    }
    .slip-subtitle {
      font-size: 10px;
      color: #64748b;
    }
    .token-box {
      background: #f0f9ff;
      border: 2px solid #0284c7;
      border-radius: 12px;
      text-align: center;
      padding: 10px 8px;
      margin: 10px 0;
    }
    .token-label {
      font-size: 9px;
      font-weight: 800;
      color: #0369a1;
      text-transform: uppercase;
      letter-spacing: 1.5px;
    }
    .token-number {
      font-size: 42px;
      font-weight: 900;
      font-family: monospace;
      color: #0c4a6e;
      line-height: 1;
      margin: 4px 0;
      letter-spacing: 2px;
    }
    .counter-badge {
      font-size: 11px;
      font-weight: 700;
      color: #0369a1;
    }
    .info-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 11px;
      margin: 10px 0;
    }
    .info-table td {
      padding: 3.5px 0;
      vertical-align: top;
    }
    .info-table .lbl {
      color: #64748b;
      width: 38%;
      font-weight: 500;
    }
    .info-table .val {
      color: #0f172a;
      font-weight: 700;
      text-align: right;
    }
    .fee-box {
      background: #ecfdf5;
      border: 1px solid #10b981;
      border-radius: 8px;
      padding: 8px 12px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin: 10px 0;
    }
    .fee-lbl {
      font-size: 11px;
      font-weight: 700;
      color: #047857;
    }
    .fee-val {
      font-size: 16px;
      font-weight: 900;
      font-family: monospace;
      color: #065f46;
    }
    .footer {
      border-top: 1px dashed #cbd5e1;
      padding-top: 8px;
      margin-top: 10px;
      text-align: center;
      font-size: 9.5px;
      color: #64748b;
      line-height: 1.4;
    }
    .status-text {
      font-weight: 700;
      color: #0284c7;
      margin-top: 2px;
    }
    .meta-text {
      margin-top: 6px;
      font-size: 8px;
      color: #94a3b8;
    }
  </style>
</head>
<body>
  <div class="header">
    <div class="hospital-title">HealthPulse Hospital &amp; Research Center</div>
    <div class="slip-title">OPD CONSULTATION SLIP</div>
    <div class="slip-subtitle">Central Reception Desk • Entry Pass</div>
  </div>

  <div class="token-box">
    <div class="token-label">Queue Token Number</div>
    <div class="token-number">${slip.token || 'N/A'}</div>
    <div class="counter-badge">Counter: ${slip.counter || 'Room 101'}</div>
  </div>

  <table class="info-table">
    <tr>
      <td class="lbl">Patient Name:</td>
      <td class="val">${slip.patientName || 'N/A'}</td>
    </tr>
    <tr>
      <td class="lbl">CNIC / ID:</td>
      <td class="val" style="font-family: monospace;">${slip.cnic || 'N/A'}</td>
    </tr>
    <tr>
      <td class="lbl">Phone:</td>
      <td class="val">${slip.phone || 'N/A'}</td>
    </tr>
    <tr>
      <td class="lbl">Specialist Doctor:</td>
      <td class="val">${slip.doctorName || 'N/A'}</td>
    </tr>
    <tr>
      <td class="lbl">Department:</td>
      <td class="val" style="color: #0284c7;">${slip.departmentName || 'General OPD'}</td>
    </tr>
    <tr>
      <td class="lbl">Scheduled Slot:</td>
      <td class="val">${slip.date || ''} · ${slip.time || ''}</td>
    </tr>
    <tr>
      <td class="lbl">Appointment Ref:</td>
      <td class="val" style="font-family: monospace;">${slip.appointmentNo || 'N/A'}</td>
    </tr>
  </table>

  <div class="fee-box">
    <span class="fee-lbl">✓ Fee Received (Cash Desk):</span>
    <span class="fee-val">$${slip.fee || '0.00'}</span>
  </div>

  <div class="footer">
    <div>Please proceed to Waiting Hall and observe the Calling Display.</div>
    <div class="status-text">Your token is now ACTIVE in the queue.</div>
    <div class="meta-text">Printed on ${new Date().toLocaleString()} | Operator Desk</div>
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

const handleDownloadSlipPdf = (data) => {
  const slip = data || checkedInSlipData.value;
  if (!slip) return;

  const doc = new jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: [100, 165]
  });

  const pageW = 100;
  let y = 10;

  // Header
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8);
  doc.setTextColor(3, 105, 161);
  doc.text('HEALTHPULSE HOSPITAL & RESEARCH CENTER', pageW / 2, y, { align: 'center' });
  y += 5;

  doc.setFontSize(13);
  doc.setTextColor(15, 23, 42);
  doc.text('OPD CONSULTATION SLIP', pageW / 2, y, { align: 'center' });
  y += 4;

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(7);
  doc.setTextColor(100, 116, 139);
  doc.text('Consultation Fee Received · Fast Track Check-In', pageW / 2, y, { align: 'center' });
  y += 6;

  // Token Box
  doc.setFillColor(240, 249, 255);
  doc.setDrawColor(2, 132, 199);
  doc.setLineWidth(0.5);
  doc.roundedRect(8, y, pageW - 16, 26, 3, 3, 'FD');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(7);
  doc.setTextColor(3, 105, 161);
  doc.text('QUEUE TOKEN NUMBER', pageW / 2, y + 5, { align: 'center' });

  doc.setFontSize(26);
  doc.setTextColor(12, 74, 110);
  doc.text(String(slip.token || 'N/A'), pageW / 2, y + 17, { align: 'center' });

  doc.setFontSize(8);
  doc.setTextColor(3, 105, 161);
  doc.text(`Counter: ${slip.counter || 'Room 101'}`, pageW / 2, y + 23, { align: 'center' });
  y += 31;

  // Details List
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

  drawRow('Patient Name:', slip.patientName, true);
  drawRow('CNIC / ID:', slip.cnic);
  drawRow('Contact Phone:', slip.phone);
  drawRow('Doctor:', slip.doctorName, true);
  drawRow('Department:', slip.departmentName);
  drawRow('Scheduled Slot:', `${slip.date || ''} · ${slip.time || ''}`);
  drawRow('Booking Ref:', slip.appointmentNo);
  y += 2;

  // Fee Box
  doc.setFillColor(236, 253, 245);
  doc.setDrawColor(16, 185, 129);
  doc.setLineWidth(0.4);
  doc.roundedRect(8, y, pageW - 16, 12, 2, 2, 'FD');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8);
  doc.setTextColor(4, 120, 87);
  doc.text('Consultation Fee Received (Cash):', 12, y + 7.5);

  doc.setFontSize(11);
  doc.setTextColor(6, 95, 70);
  doc.text(`$${slip.fee || '0.00'}`, pageW - 12, y + 7.5, { align: 'right' });
  y += 17;

  // Footer
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(6.5);
  doc.setTextColor(100, 116, 139);
  doc.text('Please proceed to Waiting Hall and wait for your Token Number.', pageW / 2, y, { align: 'center' });
  y += 4;
  doc.setFont('helvetica', 'bold');
  doc.setTextColor(3, 105, 161);
  doc.text('Your token is active in the OPD queue.', pageW / 2, y, { align: 'center' });
  y += 5;

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(6);
  doc.setTextColor(148, 163, 184);
  doc.text(`Printed: ${new Date().toLocaleString()} | HealthPulse HMS`, pageW / 2, y, { align: 'center' });

  doc.save(`HealthPulse_OPD_Slip_${slip.token || 'Slip'}.pdf`);
};

// Filtered Appointments (Searchable by Name, Token, CNIC, Phone, Doctor, Appointment No)
const filteredAppointments = computed(() => {
  if (!searchQuery.value.trim()) return appointments.value;
  const q = searchQuery.value.toLowerCase();
  return appointments.value.filter(a => 
    (a.patient?.name || '').toLowerCase().includes(q) ||
    (a.appointment_no || '').toLowerCase().includes(q) ||
    (a.token_no || '').toLowerCase().includes(q) ||
    (a.cnic || '').toLowerCase().includes(q) ||
    (a.patient?.patient_profile?.cnic || '').toLowerCase().includes(q) ||
    (a.patient?.phone || '').toLowerCase().includes(q) ||
    (a.doctor?.name || '').toLowerCase().includes(q) ||
    (a.department?.name || '').toLowerCase().includes(q)
  );
});


// Today's Appointments Pagination
const aptCurrentPage = ref(1);
const aptPerPage = ref(8);

watch(searchQuery, () => {
  aptCurrentPage.value = 1;
});

const totalAptPages = computed(() => {
  return Math.max(1, Math.ceil(filteredAppointments.value.length / aptPerPage.value));
});

const paginatedAppointments = computed(() => {
  const start = (aptCurrentPage.value - 1) * aptPerPage.value;
  return filteredAppointments.value.slice(start, start + aptPerPage.value);
});

// KPIs
const totalBeds = computed(() => wards.value.reduce((acc, w) => acc + (w.total_beds || 0), 0));
const occupiedBeds = computed(() => wards.value.reduce((acc, w) => acc + (w.beds ? w.beds.filter(b => b.is_occupied).length : 0), 0));
const availableBeds = computed(() => Math.max(0, totalBeds.value - occupiedBeds.value));

onMounted(() => {
  fetchReceptionData();
});
</script>

<template>
  <div class="min-h-screen bg-slate-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
      
      <!-- Top Title & Navigation -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-wider text-amber-700 bg-amber-100 px-3 py-1 rounded-full">
              Front Desk & Triage Operations
            </span>
            <span class="text-xs font-semibold text-slate-400">Receptionist Console</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">
            Central Reception & Inpatient Control
          </h1>
        </div>

        <div class="flex items-center gap-3">
          <router-link 
            to="/queue-display" 
            target="_blank"
            class="flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold py-2.5 px-4 rounded-xl shadow transition">
            <Tv class="w-4 h-4 text-emerald-400 animate-pulse" />
            Launch Waiting TV Screen
          </router-link>
          
          <button 
            type="button"
            @click="fetchReceptionData"
            title="Refresh Data"
            class="p-2.5 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 text-slate-600 shadow-sm transition">
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>
        </div>
      </div>

      <!-- Quick KPI Counters -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center font-bold">
            <Ticket class="w-6 h-6" />
          </div>
          <div>
            <span class="text-xs text-slate-400 font-bold block uppercase">Today's Bookings</span>
            <span class="text-2xl font-black text-slate-900">{{ appointments.length }}</span>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
            <BedDouble class="w-6 h-6" />
          </div>
          <div>
            <span class="text-xs text-slate-400 font-bold block uppercase">Available Beds</span>
            <span class="text-2xl font-black text-emerald-600">{{ availableBeds }}</span>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-bold">
            <Activity class="w-6 h-6" />
          </div>
          <div>
            <span class="text-xs text-slate-400 font-bold block uppercase">Occupied Beds</span>
            <span class="text-2xl font-black text-red-600">{{ occupiedBeds }}</span>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center font-bold">
            <Building class="w-6 h-6" />
          </div>
          <div>
            <span class="text-xs text-slate-400 font-bold block uppercase">Active Wards</span>
            <span class="text-2xl font-black text-cyan-700">{{ wards.length }}</span>
          </div>
        </div>
      </div>

      <!-- Functional Tabs Bar -->
      <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3">
        <button 
          type="button"
          @click="activeTab = 'walkin'"
          class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
          :class="activeTab === 'walkin' ? 'bg-brand-600 text-white shadow-md ring-2 ring-brand-400/30' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'">
          <Ticket class="w-4 h-4" />
          Issue Walk-In Token
        </button>

        <button 
          type="button"
          @click="activeTab = 'appointments'"
          class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
          :class="activeTab === 'appointments' ? 'bg-brand-600 text-white shadow-md ring-2 ring-brand-400/30' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'">
          <Calendar class="w-4 h-4" />
          Today's Appointments List ({{ appointments.length }})
        </button>

        <button 
          type="button"
          @click="activeTab = 'wards'"
          class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
          :class="activeTab === 'wards' ? 'bg-brand-600 text-white shadow-md ring-2 ring-brand-400/30' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'">
          <BedDouble class="w-4 h-4" />
          Ward & Bed Admissions ({{ occupiedBeds }}/{{ totalBeds }})
        </button>

        <button 
          type="button"
          @click="activeTab = 'camps'"
          class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
          :class="activeTab === 'camps' ? 'bg-teal-700 text-white shadow-md ring-2 ring-teal-400/30' : 'bg-white text-teal-800 hover:bg-teal-50 border border-teal-200'">
          <Sparkles class="w-4 h-4 text-teal-500" />
          Free Camp Registrations ({{ eventRegistrations.length }})
        </button>
      </div>

      <!-- TAB 1: Walk-In Token Generator -->
      <div v-show="activeTab === 'walkin'" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Form -->
        <div class="lg:col-span-7 bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-sm space-y-6">
          <div class="border-b border-slate-100 pb-4">
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
              <Ticket class="w-5 h-5 text-brand-600" />
              Walk-in Patient Token Dispenser
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Generate sequential queue tokens for walk-in OPD consultations</p>
          </div>

          <div v-if="generateError" class="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl flex items-center gap-2">
            <AlertCircle class="w-4 h-4 shrink-0" />
            <span>{{ generateError }}</span>
          </div>

          <form @submit.prevent="handleGenerateToken" class="space-y-4" novalidate>

            <!-- Patient Full Name -->
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                Patient Full Name <span class="text-red-500">*</span>
              </label>
              <input 
                type="text" 
                v-model="walkInForm.patient_name"
                @input="walkInErrors.patient_name = ''"
                placeholder="e.g. Ali Hassan"
                maxlength="80"
                :class="[
                  'w-full bg-slate-50 border rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:bg-white transition',
                  walkInErrors.patient_name
                    ? 'border-red-400 focus:ring-red-400 bg-red-50'
                    : 'border-slate-200 focus:ring-brand-500'
                ]"
              />
              <p v-if="walkInErrors.patient_name" class="mt-1.5 text-[11px] text-red-600 flex items-center gap-1">
                <AlertCircle class="w-3 h-3 shrink-0" />
                {{ walkInErrors.patient_name }}
              </p>
            </div>

            <!-- Contact Phone (Optional - Pakistani format) -->
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                Contact Phone
                <span class="text-slate-400 font-normal normal-case ml-1">(optional)</span>
              </label>
              <input 
                type="tel"
                v-model="walkInForm.patient_phone"
                @input="handleWalkInPhoneInput"
                placeholder="0300-1234567"
                maxlength="12"
                :class="[
                  'w-full bg-slate-50 border rounded-xl px-4 py-3 text-sm font-mono focus:outline-none focus:ring-2 focus:bg-white transition',
                  walkInErrors.patient_phone
                    ? 'border-red-400 focus:ring-red-400 bg-red-50'
                    : 'border-slate-200 focus:ring-brand-500'
                ]"
              />
              <p v-if="walkInErrors.patient_phone" class="mt-1.5 text-[11px] text-red-600 flex items-center gap-1">
                <AlertCircle class="w-3 h-3 shrink-0" />
                {{ walkInErrors.patient_phone }}
              </p>
              <p v-else class="mt-1 text-[10px] text-slate-400">Format: 03XX-XXXXXXX (11 digits, starts with 03)</p>
            </div>

            <!-- Assign Doctor -->
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                Assign Doctor &amp; Clinic <span class="text-red-500">*</span>
              </label>
              <select 
                v-model="walkInForm.doctor_id"
                @change="walkInErrors.doctor_id = ''"
                :class="[
                  'w-full bg-slate-50 border rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:bg-white transition',
                  walkInErrors.doctor_id
                    ? 'border-red-400 focus:ring-red-400 bg-red-50'
                    : 'border-slate-200 focus:ring-brand-500'
                ]">
                <option value="">Choose Doctor ({{ doctors.length }} Available)</option>
                <option v-for="doc in doctors" :key="doc.id" :value="doc.id">
                  {{ doc.name }} ({{ doc.doctor_profile?.department?.name || 'Specialist' }}) • {{ doc.doctor_profile?.room_no || 'Room 101' }}
                </option>
              </select>
              <p v-if="walkInErrors.doctor_id" class="mt-1.5 text-[11px] text-red-600 flex items-center gap-1">
                <AlertCircle class="w-3 h-3 shrink-0" />
                {{ walkInErrors.doctor_id }}
              </p>
            </div>

            <button 
              type="submit"
              :disabled="generateLoading"
              class="w-full bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white font-bold text-xs uppercase tracking-wider py-3.5 rounded-xl shadow-lg shadow-brand-500/25 transition-all flex items-center justify-center gap-2 cursor-pointer">
              <Printer class="w-4 h-4" />
              <span v-if="generateLoading">Generating Token...</span>
              <span v-else>Print &amp; Dispense Walk-In Token →</span>
            </button>
          </form>
        </div>

        <!-- Token Voucher Card / Preview -->
        <div class="lg:col-span-5 flex flex-col justify-center">
          <div v-if="tokenResult" class="bg-gradient-to-br from-brand-950 to-slate-900 text-white rounded-3xl p-6 sm:p-8 border-2 border-brand-500 shadow-2xl space-y-5 animate-in fade-in">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
              <span class="text-xs font-bold uppercase tracking-widest text-emerald-400 flex items-center gap-1.5">
                <CheckCircle2 class="w-4 h-4" /> Token Dispensed
              </span>
              <button @click="tokenResult = null" class="text-xs text-slate-400 hover:text-white">✕ Clear</button>
            </div>

            <div class="text-center py-4 bg-slate-900/80 rounded-2xl border border-slate-800">
              <span class="text-[11px] uppercase font-bold text-brand-300 block mb-1">Queue Token Number</span>
              <span class="text-5xl font-black font-mono tracking-widest text-white drop-shadow">
                {{ tokenResult.token_number }}
              </span>
            </div>

            <div class="space-y-2 text-xs text-slate-300">
              <div class="flex justify-between py-1 border-b border-slate-800">
                <span class="text-slate-400">Patient Name:</span>
                <strong class="text-white">{{ tokenResult.patient_name }}</strong>
              </div>
              <div class="flex justify-between py-1 border-b border-slate-800">
                <span class="text-slate-400">Assigned Counter:</span>
                <strong class="text-emerald-300">{{ tokenResult.counter_no }}</strong>
              </div>
              <div class="flex justify-between py-1">
                <span class="text-slate-400">Doctor:</span>
                <strong class="text-white">{{ tokenResult.doctor?.name }}</strong>
              </div>
            </div>

            <div class="text-[11px] text-slate-400 text-center pt-1">
              Patient is now queued. Audio announcement will trigger automatically on Waiting TV screen.
            </div>

            <div class="pt-2 flex gap-2">
              <button 
                type="button"
                @click="handlePrintSlip({
                  token: tokenResult.token_number,
                  patientName: tokenResult.patient_name,
                  doctorName: tokenResult.doctor?.name,
                  departmentName: 'OPD Walk-In',
                  counter: tokenResult.counter_no,
                  fee: '50.00',
                  date: new Date().toISOString().split('T')[0],
                  time: 'Immediate',
                  appointmentNo: 'WALK-IN-' + tokenResult.token_number,
                  cnic: 'Desk Walk-In',
                  phone: tokenResult.patient_phone || 'N/A'
                })"
                class="flex-1 flex items-center justify-center gap-1.5 bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs py-2.5 rounded-xl transition shadow-md cursor-pointer">
                <Printer class="w-3.5 h-3.5" /> Print Walk-In Slip
              </button>
              <button 
                type="button"
                @click="handleDownloadSlipPdf({
                  token: tokenResult.token_number,
                  patientName: tokenResult.patient_name,
                  doctorName: tokenResult.doctor?.name,
                  departmentName: 'OPD Walk-In',
                  counter: tokenResult.counter_no,
                  fee: '50.00',
                  date: new Date().toISOString().split('T')[0],
                  time: 'Immediate',
                  appointmentNo: 'WALK-IN-' + tokenResult.token_number,
                  cnic: 'Desk Walk-In',
                  phone: tokenResult.patient_phone || 'N/A'
                })"
                class="flex items-center justify-center gap-1.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs px-3 py-2.5 rounded-xl transition cursor-pointer">
                <Download class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <div v-else class="bg-white rounded-3xl p-8 border border-dashed border-slate-300 text-center text-slate-400 space-y-3">
            <Ticket class="w-12 h-12 mx-auto text-slate-300" />
            <h4 class="font-bold text-slate-600 text-sm">No Token Generated Yet</h4>
            <p class="text-xs text-slate-400">Fill the form on the left to dispense an instant patient token.</p>
          </div>
        </div>

      </div>

      <!-- TAB 2: Scheduled Appointments List & Search -->
      <div v-show="activeTab === 'appointments'" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
          <div>
            <h3 class="text-lg font-bold text-slate-900">Scheduled Appointments</h3>
            <p class="text-xs text-slate-400">Search and verify pre-booked patient slots</p>
          </div>

          <!-- Search Bar -->
          <div class="relative w-full sm:w-72">
            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
            <input 
              type="text" 
              v-model="searchQuery"
              placeholder="Search patient, token, doctor..."
              class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white"
            />
          </div>
        </div>

        <!-- Success Notice Banner -->
        <div v-if="checkInSuccessNotice" class="p-4 bg-emerald-600 text-white rounded-2xl flex items-center justify-between text-xs animate-in fade-in">
          <div class="flex items-center gap-2">
            <CheckCircle2 class="w-4 h-4 text-emerald-200 flex-shrink-0" />
            <span class="font-bold">{{ checkInSuccessNotice }}</span>
          </div>
          <button @click="checkInSuccessNotice = ''" class="text-xs text-white/80 hover:text-white font-bold cursor-pointer">✕ Dismiss</button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="text-slate-400 border-b border-slate-100 uppercase">
                <th class="pb-3 font-bold">Token</th>
                <th class="pb-3 font-bold">Patient & CNIC</th>
                <th class="pb-3 font-bold">Doctor & Department</th>
                <th class="pb-3 font-bold">Time Slot</th>
                <th class="pb-3 font-bold">Slot Status</th>
                <th class="pb-3 font-bold">Fee Status</th>
                <th class="pb-3 font-bold text-right">Front Desk Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="apt in paginatedAppointments" :key="apt.id" class="hover:bg-slate-50 transition">
                <td class="py-3.5 font-mono font-bold text-brand-700">
                  <span class="bg-brand-50 border border-brand-200 px-2.5 py-1 rounded-md">
                    {{ apt.token_no || 'T-001' }}
                  </span>
                </td>
                <td class="py-3.5">
                  <div class="font-bold text-slate-900">{{ apt.patient?.name }}</div>
                  <div class="text-[11px] text-slate-500 flex items-center gap-1.5">
                    <span>{{ apt.patient?.phone }}</span>
                    <span v-if="apt.cnic || apt.patient?.patient_profile?.cnic" class="text-brand-600 font-mono">
                      • {{ apt.cnic || apt.patient?.patient_profile?.cnic }}
                    </span>
                  </div>
                </td>
                <td class="py-3.5">
                  <div class="font-semibold text-slate-800">{{ apt.doctor?.name }}</div>
                  <div class="text-[11px] text-brand-600 font-medium">{{ apt.department?.name }}</div>
                </td>
                <td class="py-3.5 text-slate-600 font-medium">
                  {{ apt.appointment_date }} ({{ apt.time_slot }})
                </td>
                <td class="py-3.5">
                  <span 
                    class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase"
                    :class="{
                      'bg-emerald-100 text-emerald-700': apt.status === 'completed',
                      'bg-amber-100 text-amber-700 animate-pulse': apt.status === 'in_consultation',
                      'bg-blue-100 text-blue-700': apt.status === 'confirmed',
                      'bg-slate-100 text-slate-600': apt.status === 'pending'
                    }">
                    {{ apt.status }}
                  </span>
                </td>
                <td class="py-3.5">
                  <span 
                    v-if="apt.payment_status === 'paid'"
                    class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <CheckCircle2 class="w-3 h-3" /> Paid (${{ apt.fee }})
                  </span>
                  <span 
                    v-else
                    class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200">
                    <DollarSign class="w-3 h-3" /> Fee Due: ${{ apt.fee }}
                  </span>
                </td>
                <td class="py-3.5 text-right">
                  <button 
                    v-if="apt.payment_status !== 'paid'"
                    @click="handleCheckIn(apt)"
                    :disabled="checkInUpdatingId === apt.id"
                    class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] px-3.5 py-1.5 rounded-xl shadow-sm transition-all disabled:opacity-50 cursor-pointer">
                    <DollarSign class="w-3.5 h-3.5" />
                    <span v-if="checkInUpdatingId === apt.id">Processing...</span>
                    <span v-else>Collect ${{ apt.fee }} & Check-In</span>
                  </button>
                  <div v-else class="inline-flex items-center gap-2">
                    <span class="text-[11px] font-bold text-emerald-600 flex items-center gap-1">
                      <CheckCircle2 class="w-3.5 h-3.5" /> Checked In
                    </span>
                    <button 
                      @click="checkedInSlipData = {
                        token: apt.token_no,
                        patientName: apt.patient?.name,
                        doctorName: apt.doctor?.name,
                        departmentName: apt.department?.name || 'General OPD',
                        counter: apt.opd_queue?.counter_no || apt.doctor?.doctor_profile?.room_no || 'Room 101',
                        fee: apt.fee,
                        date: apt.appointment_date,
                        time: apt.time_slot,
                        appointmentNo: apt.appointment_no,
                        cnic: apt.cnic || apt.patient?.patient_profile?.cnic || 'N/A',
                        phone: apt.patient?.phone || 'N/A',
                      }"
                      class="text-brand-600 hover:text-brand-800 text-[11px] font-bold underline cursor-pointer"
                      title="View Entry Slip">
                      Print Slip
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredAppointments.length === 0">
                <td colspan="7" class="py-8 text-center text-slate-400">
                  No appointments found matching your search.
                </td>
              </tr>
            </tbody>
          </table>
        </div>


        <!-- Appointments Pagination Controls -->
        <div v-if="filteredAppointments.length > 0" class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
          <span class="text-slate-500 font-medium">
            Showing <span class="font-bold text-slate-800">{{ (aptCurrentPage - 1) * aptPerPage + 1 }}</span> to 
            <span class="font-bold text-slate-800">{{ Math.min(aptCurrentPage * aptPerPage, filteredAppointments.length) }}</span> 
            of <span class="font-bold text-slate-800">{{ filteredAppointments.length }}</span> appointments
          </span>

          <div v-if="totalAptPages > 1" class="flex items-center gap-1.5">
            <button 
              type="button"
              @click="aptCurrentPage = Math.max(1, aptCurrentPage - 1)"
              :disabled="aptCurrentPage === 1"
              class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none transition cursor-pointer"
              title="Previous Page">
              <ChevronLeft class="w-4 h-4" />
            </button>

            <div class="flex items-center gap-1">
              <button 
                v-for="page in totalAptPages" 
                :key="page"
                type="button"
                @click="aptCurrentPage = page"
                class="w-7 h-7 rounded-lg text-xs font-bold transition flex items-center justify-center cursor-pointer"
                :class="aptCurrentPage === page ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'">
                {{ page }}
              </button>
            </div>

            <button 
              type="button"
              @click="aptCurrentPage = Math.min(totalAptPages, aptCurrentPage + 1)"
              :disabled="aptCurrentPage === totalAptPages"
              class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none transition cursor-pointer"
              title="Next Page">
              <ChevronRight class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- TAB 3: Inpatient Wards & Beds -->
      <div v-show="activeTab === 'wards'" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-sm space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
          <div>
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
              <BedDouble class="w-5 h-5 text-cyan-600" />
              Inpatient Wards & Bed Occupancy Matrix
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Click any available bed to admit a patient, or an occupied bed to discharge</p>
          </div>
          <span class="text-xs font-bold text-slate-600 bg-slate-100 px-3 py-1 rounded-full">
            {{ occupiedBeds }} Beds Occupied / {{ totalBeds }} Total
          </span>
        </div>

        <!-- Wards Matrix -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div 
            v-for="w in wards" 
            :key="w.id"
            class="bg-slate-50 rounded-2xl p-5 border border-slate-200 space-y-3">
            
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-bold text-slate-900">{{ w.name }}</h4>
                <p class="text-[11px] text-slate-500">{{ w.floor }} • ${{ w.daily_rate }}/day</p>
              </div>
              <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full uppercase bg-white border border-slate-200 text-slate-600">
                {{ w.beds ? w.beds.filter(b => b.is_occupied).length : 0 }} / {{ w.total_beds }} Occupied
              </span>
            </div>

            <!-- Beds Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 pt-2">
              <div 
                v-for="b in w.beds" 
                :key="b.id"
                @click="b.is_occupied ? handleDischarge(b) : openAdmit(b)"
                class="p-3 rounded-xl border text-center transition-all cursor-pointer shadow-sm hover:scale-105"
                :class="b.is_occupied ? 'bg-red-50 border-red-200 text-red-900' : 'bg-emerald-50 border-emerald-200 text-emerald-900'">
                
                <span class="text-xs font-mono font-extrabold block">{{ b.bed_number }}</span>
                <span class="text-[10px] font-bold block mt-1 truncate">
                  {{ b.is_occupied ? b.patient_name || 'Occupied' : 'AVAILABLE' }}
                </span>
                <span class="text-[9px] text-slate-500 block">
                  {{ b.is_occupied ? 'Click to Discharge' : 'Click to Admit' }}
                </span>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- TAB 4: Free Camp Registrations Records -->
      <div v-show="activeTab === 'camps'" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-sm space-y-6">
        
        <!-- Header & Stats -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
          <div>
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
              <Sparkles class="w-5 h-5 text-teal-600" />
              Community Health Camps Attendance & Pass Verification
            </h3>
            <p class="text-xs text-slate-400">Search passes, verify incoming citizens, and mark them as Attended / Completed on camp day</p>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-teal-800 bg-teal-50 border border-teal-200 px-3.5 py-1.5 rounded-xl flex items-center gap-1.5">
              <Ticket class="w-3.5 h-3.5 text-teal-600" />
              Total Passes: {{ eventRegistrations.length }}
            </span>
          </div>
        </div>

        <!-- Success notification toast -->
        <div v-if="campSuccessNotice" class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl flex items-center gap-2 shadow-sm animate-in fade-in">
          <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
          <span class="font-bold">{{ campSuccessNotice }}</span>
        </div>

        <!-- Search & Filter Controls -->
        <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
          
          <!-- Search Bar -->
          <div class="relative w-full sm:w-96">
            <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
            <input 
              type="text"
              v-model="campSearchQuery"
              placeholder="Search by Pass Code (CMP-...), Name, CNIC, Phone..."
              class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500"
            />
          </div>

          <!-- Status Filters -->
          <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl w-full sm:w-auto">
            <button 
              type="button"
              @click="campStatusFilter = 'all'"
              class="px-3 py-1.5 text-xs font-bold rounded-lg transition"
              :class="campStatusFilter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900'">
              All ({{ eventRegistrations.length }})
            </button>
            <button 
              type="button"
              @click="campStatusFilter = 'confirmed'"
              class="px-3 py-1.5 text-xs font-bold rounded-lg transition"
              :class="campStatusFilter === 'confirmed' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900'">
              Confirmed ({{ eventRegistrations.filter(r => (r.status || 'confirmed') === 'confirmed').length }})
            </button>
            <button 
              type="button"
              @click="campStatusFilter = 'completed'"
              class="px-3 py-1.5 text-xs font-bold rounded-lg transition"
              :class="campStatusFilter === 'completed' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900'">
              Attended / Completed ({{ eventRegistrations.filter(r => r.status === 'completed' || r.status === 'attended').length }})
            </button>
          </div>
        </div>

        <!-- Table View -->
        <div v-if="filteredCampRegistrations.length > 0" class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="text-slate-400 border-b border-slate-100 uppercase font-bold text-[11px]">
                <th class="pb-3">Pass ID</th>
                <th class="pb-3">Attendee & Identity</th>
                <th class="pb-3">Medical Camp Event</th>
                <th class="pb-3">Concern & Age</th>
                <th class="pb-3">Group</th>
                <th class="pb-3">Status</th>
                <th class="pb-3 text-right">Action (Reception Verification)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="reg in filteredCampRegistrations" :key="reg.id" class="hover:bg-slate-50/80 transition">
                
                <!-- Pass Code -->
                <td class="py-3.5 font-mono font-black text-teal-800">
                  <span class="bg-teal-50 border border-teal-200 px-2.5 py-1 rounded-lg">
                    {{ reg.pass_code }}
                  </span>
                </td>

                <!-- Attendee Info -->
                <td class="py-3.5">
                  <div class="font-bold text-slate-900 text-xs">{{ reg.attendee_name }}</div>
                  <div class="text-[11px] text-teal-700 font-mono flex items-center gap-1.5 mt-0.5">
                    <span>{{ reg.cnic_id }}</span>
                    <span>•</span>
                    <span>{{ reg.attendee_phone }}</span>
                  </div>
                </td>

                <!-- Camp Event -->
                <td class="py-3.5">
                  <div class="font-semibold text-slate-800">{{ reg.event?.title || 'Community Health Camp' }}</div>
                  <div class="text-[11px] text-slate-400">{{ reg.event?.event_date ? new Date(reg.event.event_date).toLocaleDateString() : 'Upcoming' }} • {{ reg.event?.time_slot }}</div>
                </td>

                <!-- Concern & Age -->
                <td class="py-3.5">
                  <div class="text-slate-700 font-medium">{{ reg.health_concern }}</div>
                  <div class="text-[10px] text-slate-400">{{ reg.age_group }}</div>
                </td>

                <!-- Persons Count -->
                <td class="py-3.5 font-bold text-slate-800">
                  {{ reg.attendees_count }} Person(s)
                </td>

                <!-- Status Badge -->
                <td class="py-3.5">
                  <span 
                    v-if="reg.status === 'completed' || reg.status === 'attended'"
                    class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1 w-fit">
                    <CheckCircle2 class="w-3 h-3 text-emerald-600" /> ATTENDED / COMPLETED
                  </span>
                  <span 
                    v-else
                    class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-800 border border-amber-300 flex items-center gap-1 w-fit">
                    <Clock class="w-3 h-3 text-amber-600" /> CONFIRMED (Awaiting)
                  </span>
                </td>

                <!-- Reception Action Button -->
                <td class="py-3.5 text-right">
                  <button 
                    v-if="reg.status !== 'completed' && reg.status !== 'attended'"
                    type="button"
                    @click="handleUpdateCampStatus(reg, 'completed')"
                    :disabled="campUpdatingId === reg.id"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition cursor-pointer disabled:opacity-50">
                    <CheckCircle2 class="w-3.5 h-3.5" />
                    <span>{{ campUpdatingId === reg.id ? 'Updating...' : 'Mark Attended ✓' }}</span>
                  </button>

                  <button 
                    v-else
                    type="button"
                    @click="handleUpdateCampStatus(reg, 'confirmed')"
                    :disabled="campUpdatingId === reg.id"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-100 border border-slate-200 transition cursor-pointer">
                    <span>Revert to Pending</span>
                  </button>
                </td>

              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="py-12 text-center text-slate-400 space-y-2">
          <Sparkles class="w-10 h-10 mx-auto text-slate-300" />
          <h4 class="font-bold text-slate-600 text-sm">No Matching Camp Registrations</h4>
          <p class="text-xs text-slate-400">Try changing your search query or filter selection.</p>
        </div>
      </div>

    </div>

    <!-- Bed Admission Modal -->
    <div v-if="admitModalOpen" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-slate-100 space-y-4">
        <h3 class="text-lg font-bold text-slate-900">
          Admit Patient to Bed #{{ selectedBed?.bed_number }}
        </h3>
        <p class="text-xs text-slate-500">
          Complete patient admission record for inpatient bed allocation.
        </p>

        <div class="space-y-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Patient Full Name <span class="text-red-500">*</span></label>
            <input 
              type="text" 
              v-model="admitPatientName"
              required
              placeholder="e.g. John Doe"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Admission Reason / Triage Notes</label>
            <textarea 
              v-model="admitDiagnosis"
              rows="2"
              placeholder="e.g. Post-op recovery, Cardiac observation..."
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm"
            ></textarea>
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <button @click="admitModalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-600 rounded-xl">
            Cancel
          </button>
          <button 
            @click="handleAdmit" 
            :disabled="bedActionLoading"
            class="px-5 py-2 text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-xl shadow-md transition">
            <span v-if="bedActionLoading">Admitting...</span>
            <span v-else>Confirm Admission</span>
          </button>
        </div>
      </div>
    </div>

    <!-- OPD Entry Slip / Receipt Modal -->
    <div v-if="checkedInSlipData" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-sm w-full shadow-2xl border border-slate-100 overflow-hidden">
        <!-- Slip Header -->
        <div class="bg-gradient-to-r from-brand-900 to-brand-700 text-white px-6 py-5 text-center">
          <div class="text-xs font-extrabold uppercase tracking-widest text-brand-200 mb-1">HealthPulse Hospital & Research Center</div>
          <div class="text-lg font-black">OPD Entry Receipt & Slip</div>
          <div class="text-[11px] text-brand-200 mt-0.5">Consultation Fee Received · Fast Track Check-In</div>
        </div>

        <!-- Token -->
        <div class="bg-brand-50 border-b border-brand-100 px-6 py-4 text-center">
          <div class="text-[10px] font-bold uppercase tracking-widest text-brand-500 mb-1">Queue Token Number</div>
          <div class="text-5xl font-black font-mono text-brand-900 tracking-widest">{{ checkedInSlipData.token }}</div>
          <div class="text-xs text-brand-600 font-bold mt-1">Counter: {{ checkedInSlipData.counter }}</div>
        </div>

        <!-- Details -->
        <div class="px-6 py-4 space-y-2.5 text-xs text-slate-700 border-b border-slate-100">
          <div class="flex justify-between">
            <span class="text-slate-500">Patient Name:</span>
            <strong class="text-slate-900">{{ checkedInSlipData.patientName }}</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">CNIC:</span>
            <strong class="font-mono text-slate-900">{{ checkedInSlipData.cnic }}</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Phone:</span>
            <strong class="text-slate-900">{{ checkedInSlipData.phone }}</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Specialist Doctor:</span>
            <strong class="text-slate-900">{{ checkedInSlipData.doctorName }}</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Department:</span>
            <strong class="text-brand-700">{{ checkedInSlipData.departmentName }}</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Scheduled Slot:</span>
            <strong>{{ checkedInSlipData.date }} · {{ checkedInSlipData.time }}</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Appointment Ref:</span>
            <strong class="font-mono text-brand-700">{{ checkedInSlipData.appointmentNo }}</strong>
          </div>
        </div>

        <!-- Fee Received -->
        <div class="px-6 py-3.5 bg-emerald-50 border-b border-emerald-100 flex justify-between items-center">
          <div class="flex items-center gap-2 text-emerald-700">
            <CheckCircle2 class="w-4 h-4" />
            <span class="font-bold text-sm">Consultation Fee Received</span>
          </div>
          <span class="text-xl font-black font-mono text-emerald-800">${{ checkedInSlipData.fee }}</span>
        </div>

        <!-- Footer Notice -->
        <div class="px-6 py-4 text-[11px] text-slate-500 text-center border-b border-slate-100">
          Please proceed to Waiting Hall and wait for your Token Number to be called.<br/>
          <span class="font-bold text-brand-600">Your token is now active in the queue.</span>
        </div>

        <!-- Actions -->
        <div class="px-6 py-4 flex flex-wrap gap-2.5 justify-center">
          <button 
            @click="handlePrintSlip(checkedInSlipData)"
            class="flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-md transition cursor-pointer">
            <Printer class="w-3.5 h-3.5" /> Print Slip
          </button>
          <button 
            @click="handleDownloadSlipPdf(checkedInSlipData)"
            class="flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-md transition cursor-pointer">
            <Download class="w-3.5 h-3.5" /> Download PDF
          </button>
          <button 
            @click="checkedInSlipData = null"
            class="flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl transition cursor-pointer">
            <X class="w-3.5 h-3.5" /> Close
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

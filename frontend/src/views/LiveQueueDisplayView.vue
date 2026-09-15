<script setup>
import { onMounted, onUnmounted, ref, computed, watch } from 'vue';
import { useQueueStore } from '../stores/queueStore';
import { 
  Tv, Volume2, VolumeX, Clock, HeartPulse, 
  Sparkles, CheckCircle2, UserCheck, AlertCircle, ArrowRight,
  Filter, PlayCircle, Users, Activity, Building2, Stethoscope
} from 'lucide-vue-next';

const queueStore = useQueueStore();
const currentTime = ref(new Date().toLocaleTimeString());
const audioUnlocked = ref(false);
let timer = null;

// Waiting list pagination
const itemsPerPage = 6;
const currentPage = ref(1);

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(queueStore.waitingList.length / itemsPerPage));
});

const paginatedWaitingList = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return queueStore.waitingList.slice(start, start + itemsPerPage);
});

watch(() => queueStore.waitingList.length, () => {
  if (currentPage.value > totalPages.value) {
    currentPage.value = 1;
  }
});

const setPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
  }
};

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
  }
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
  }
};

// Called on first user click/tap - unlocks browser audio autoplay
const unlockAudio = () => {
  if (audioUnlocked.value) return;
  audioUnlocked.value = true;

  // 1. Pre-warm AudioContext (removes suspended state)
  try {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (AudioContext) {
      const ctx = new AudioContext();
      const buffer = ctx.createBuffer(1, 1, 22050);
      const source = ctx.createBufferSource();
      source.buffer = buffer;
      source.connect(ctx.destination);
      source.start(0);
      window._hmsAudioCtx = ctx;
    }
  } catch (e) {
    console.warn('AudioContext pre-warm failed:', e);
  }

  // 2. Pre-warm speechSynthesis
  if ('speechSynthesis' in window) {
    const warmup = new SpeechSynthesisUtterance(' ');
    warmup.volume = 0;
    window.speechSynthesis.speak(warmup);
  }

  // 3. Play welcome chime to confirm audio is active
  setTimeout(() => {
    queueStore.playHospitalChime();
    if (queueStore.currentlyCalling.length > 0) {
      setTimeout(() => {
        queueStore.triggerCallAnnouncement(queueStore.currentlyCalling[0]);
      }, 800);
    }
  }, 300);
};

onMounted(() => {
  queueStore.startPolling(3000);
  timer = setInterval(() => {
    currentTime.value = new Date().toLocaleTimeString();
  }, 1000);
});

onUnmounted(() => {
  queueStore.stopPolling();
  if (timer) clearInterval(timer);
});

const handleDeptFilter = (deptId) => {
  queueStore.setDepartmentFilter(deptId);
};

// Replay audio announcement for currently calling patient
const replayAnnouncement = (token = null) => {
  const target = token || queueStore.currentlyCalling[0];
  if (target) {
    queueStore.triggerCallAnnouncement(target);
    queueStore.flashScreen();
  }
};
</script>

<template>
  <div 
    class="min-h-screen bg-slate-950 text-white flex flex-col justify-between p-4 sm:p-8 select-none transition-all duration-500 relative overflow-x-hidden"
    :class="{ 'ring-8 ring-emerald-500/80 bg-slate-900': queueStore.isFlashing }">

    <!-- AUDIO UNLOCK OVERLAY: Shows until user clicks anywhere -->
    <div 
      v-if="!audioUnlocked"
      @click="unlockAudio"
      class="fixed inset-0 z-[100] flex flex-col items-center justify-center cursor-pointer"
      style="background: rgba(2,8,23,0.92); backdrop-filter: blur(8px);">
      <div class="text-center space-y-6 px-6">
        <div class="w-24 h-24 mx-auto rounded-full bg-gradient-to-br from-brand-600 to-cyan-500 flex items-center justify-center shadow-2xl shadow-brand-500/40 animate-pulse">
          <Volume2 class="w-12 h-12 text-white" />
        </div>
        <div>
          <h2 class="text-3xl sm:text-4xl font-black text-white">HealthPulse OPD Queue</h2>
          <p class="text-brand-300 font-semibold mt-1 text-sm">Live Patient Calling System</p>
        </div>
        <div class="bg-emerald-500/10 border-2 border-emerald-400/60 rounded-2xl px-8 py-5 max-w-sm mx-auto">
          <p class="text-emerald-300 font-bold text-lg">👆 Tap Anywhere to Enable Audio</p>
          <p class="text-slate-400 text-xs mt-2">Patient announcements will play automatically once audio is enabled</p>
        </div>
        <p class="text-slate-500 text-xs">Required by browser security policy • One-time only</p>
      </div>
    </div>
    
    <!-- Top Header Bar -->
    <header class="flex flex-wrap items-center justify-between border-b border-slate-800 pb-5 gap-4">
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-brand-600 to-cyan-500 flex items-center justify-center shadow-lg shadow-brand-500/30">
          <HeartPulse class="w-8 h-8 text-white animate-pulse" />
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">HealthPulse Hospital</h1>
            <span class="bg-emerald-500/20 border border-emerald-400/40 text-emerald-300 text-xs font-bold px-2.5 py-0.5 rounded-full uppercase flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
              Live OPD Board
            </span>
          </div>
          <p class="text-xs text-slate-400 tracking-wider uppercase font-semibold">Central Waiting Room Electronic Queue System & Smart Audio Calling</p>
        </div>
      </div>

      <!-- Controls & Digital Clock -->
      <div class="flex items-center flex-wrap gap-3 sm:gap-4">
        <!-- Audio Announcement Toggle -->
        <button 
          @click="queueStore.audioAlertEnabled = !queueStore.audioAlertEnabled"
          class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all"
          :class="queueStore.audioAlertEnabled ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-slate-800 text-slate-400 border border-slate-700'">
          <Volume2 v-if="queueStore.audioAlertEnabled" class="w-4 h-4 text-emerald-400 animate-pulse" />
          <VolumeX v-else class="w-4 h-4 text-slate-500" />
          Audio {{ queueStore.audioAlertEnabled ? 'ON' : 'MUTED' }}
        </button>

        <!-- Digital Clock -->
        <div class="bg-slate-900 border border-slate-800 px-5 py-2 rounded-2xl flex items-center gap-2 shadow-inner">
          <Clock class="w-5 h-5 text-brand-400" />
          <span class="text-xl sm:text-2xl font-black font-mono text-white tracking-widest">{{ currentTime }}</span>
        </div>
      </div>
    </header>

    <!-- Department Filter Bar (Quick Select) -->
    <div class="flex items-center gap-2 py-3 overflow-x-auto no-scrollbar border-b border-slate-800/60 my-2">
      <span class="text-xs font-bold text-slate-400 uppercase flex items-center gap-1 shrink-0 mr-2">
        <Filter class="w-3.5 h-3.5 text-brand-400" /> Filter:
      </span>
      <button 
        @click="handleDeptFilter(null)"
        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0"
        :class="queueStore.selectedDepartment === null ? 'bg-brand-600 text-white shadow' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'">
        All Clinics
      </button>
      <button 
        v-for="dept in queueStore.departments" 
        :key="dept.id"
        @click="handleDeptFilter(dept.id)"
        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5"
        :class="queueStore.selectedDepartment === dept.id ? 'bg-brand-600 text-white shadow' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'">
        {{ dept.name }}
      </button>
    </div>

    <!-- Main Content Grid -->
    <main class="grid grid-cols-1 lg:grid-cols-12 gap-6 my-4 flex-1 items-stretch">
      
      <!-- Left Column: Currently Calling Patients (Primary Stage) -->
      <div class="lg:col-span-7 flex flex-col justify-between space-y-4">
        
        <!-- Primary Big Call Card -->
        <div class="bg-gradient-to-br from-brand-950/90 via-slate-900 to-slate-900 border-2 border-brand-500/40 rounded-3xl p-6 sm:p-10 shadow-2xl shadow-brand-500/10 flex-1 flex flex-col justify-between relative overflow-hidden">
          
          <div class="absolute -right-16 -top-16 w-64 h-64 bg-brand-500/10 rounded-full blur-3xl"></div>

          <!-- Header -->
          <div class="flex items-center justify-between pb-4 border-b border-slate-800">
            <div class="flex items-center gap-2">
              <span class="w-3.5 h-3.5 rounded-full bg-emerald-400 animate-ping"></span>
              <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-400">
                NOW CALLING / PROCEED TO CONSULTATION
              </span>
            </div>
            
            <!-- Repeat Audio Button -->
            <button 
              v-if="queueStore.currentlyCalling.length > 0"
              @click="replayAnnouncement()"
              class="flex items-center gap-1.5 text-xs font-bold text-brand-300 hover:text-white bg-brand-600/30 hover:bg-brand-600/60 border border-brand-400/40 px-3 py-1.5 rounded-xl transition-all shadow">
              <Volume2 class="w-3.5 h-3.5 text-emerald-400" />
              Repeat Announcement
            </button>
            <span v-else class="text-xs font-semibold text-slate-400">Please have your MRN card ready</span>
          </div>

          <!-- Hero Active Token Display -->
          <div v-if="queueStore.currentlyCalling.length > 0" class="my-auto py-6 text-center space-y-4">
            <div class="inline-block bg-brand-500/20 border-2 border-brand-400/60 px-12 py-6 rounded-3xl backdrop-blur-md shadow-2xl animate-pulse">
              <span class="text-xs uppercase font-extrabold tracking-widest text-brand-300 block mb-1">Queue Token</span>
              <span class="text-6xl sm:text-8xl font-black tracking-widest font-mono text-white drop-shadow-xl">
                {{ queueStore.currentlyCalling[0].token_number }}
              </span>
            </div>

            <div class="space-y-2 pt-2">
              <h2 class="text-3xl sm:text-4xl font-extrabold text-white">
                {{ queueStore.currentlyCalling[0].patient_name }}
              </h2>
              <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                <span class="bg-emerald-600 text-white font-black text-base sm:text-lg px-6 py-2 rounded-2xl shadow-lg border border-emerald-400/40">
                  {{ queueStore.currentlyCalling[0].counter_no }}
                </span>
                <span class="bg-slate-800 text-slate-200 text-sm sm:text-base font-semibold px-5 py-2 rounded-2xl border border-slate-700 flex items-center gap-2">
                  <Stethoscope class="w-4 h-4 text-brand-400" />
                  {{ queueStore.currentlyCalling[0].doctor?.name }} ({{ queueStore.currentlyCalling[0].department?.name }})
                </span>
              </div>
            </div>
          </div>

          <div v-else class="my-auto py-12 text-center space-y-4">
            <div class="w-16 h-16 rounded-full bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
              <UserCheck class="w-8 h-8" />
            </div>
            <h3 class="text-xl font-bold text-slate-400">Consultations In Progress</h3>
            <p class="text-xs text-slate-500">Next patient token will be called momentarily.</p>
          </div>

          <!-- Bottom Notice -->
          <div class="pt-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
            <span>Room guidance available at Central OPD Reception Desk</span>
            <span class="text-brand-400 font-semibold">Total Handled Today: {{ queueStore.totalToday }}</span>
          </div>

        </div>

        <!-- Secondary Multi-Room Active Calling Bar (if multiple doctors are calling simultaneously) -->
        <div v-if="queueStore.currentlyCalling.length > 1" class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
          <h4 class="text-xs font-bold text-slate-400 uppercase mb-3 flex items-center gap-1.5">
            <Building2 class="w-4 h-4 text-brand-400" /> Also Calling in Other Clinics:
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div 
              v-for="call in queueStore.currentlyCalling.slice(1, 3)" 
              :key="call.id"
              class="bg-slate-800/80 border border-slate-700 p-3 rounded-xl flex items-center justify-between">
              <div>
                <p class="text-xs font-bold text-white">{{ call.patient_name }}</p>
                <p class="text-[11px] text-slate-400">{{ call.counter_no }} • {{ call.department?.name }}</p>
              </div>
              <div class="flex items-center gap-2">
                <button 
                  @click="replayAnnouncement(call)"
                  title="Repeat call for this room"
                  class="p-1.5 rounded-lg bg-slate-900 text-brand-300 hover:text-white border border-slate-700 transition">
                  <Volume2 class="w-3.5 h-3.5 text-emerald-400" />
                </button>
                <span class="text-lg font-black font-mono text-emerald-400 bg-slate-900 px-3 py-1 rounded-lg border border-slate-700">
                  {{ call.token_number }}
                </span>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Right Column: Waiting Queue Stream with Pagination -->
      <div class="lg:col-span-5 flex flex-col justify-between space-y-4">
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 flex-1 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-4">
              <div>
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                  <Users class="w-5 h-5 text-brand-400" />
                  Up Next In Waiting Room
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Live queue stream for pending consultations</p>
              </div>
              <span class="text-xs font-bold text-brand-300 bg-brand-950 px-3 py-1 rounded-full border border-brand-800">
                {{ queueStore.waitingList.length }} Waiting
              </span>
            </div>

            <!-- Waiting Tokens List -->
            <div v-if="paginatedWaitingList.length > 0" class="space-y-2.5 min-h-[380px]">
              <div 
                v-for="(w, idx) in paginatedWaitingList" 
                :key="w.id"
                class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-3 flex items-center justify-between transition-all hover:bg-slate-800">
                
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-xl bg-slate-900 border border-slate-700 flex items-center justify-center font-mono font-bold text-xs text-brand-400">
                    #{{ (currentPage - 1) * itemsPerPage + idx + 1 }}
                  </div>
                  <div>
                    <h4 class="text-sm font-bold text-white">{{ w.patient_name }}</h4>
                    <p class="text-[11px] text-slate-400">{{ w.doctor?.name }} • {{ w.counter_no }}</p>
                  </div>
                </div>

                <span class="text-base font-mono font-black text-brand-300 bg-slate-900/90 px-3 py-1 rounded-xl border border-slate-700">
                  {{ w.token_number }}
                </span>
              </div>
            </div>

            <div v-else class="py-16 text-center text-slate-500 text-xs">
              No patients currently waiting in line.
            </div>
          </div>

          <!-- Pagination Bar (if multiple pages exist) -->
          <div class="mt-4 pt-3 border-t border-slate-800 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-400">
            <div class="flex items-center gap-2 font-semibold">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              <span class="text-slate-300 font-bold">
                Showing {{ (currentPage - 1) * itemsPerPage + 1 }}–{{ Math.min(currentPage * itemsPerPage, queueStore.waitingList.length) }} of {{ queueStore.waitingList.length }}
              </span>
            </div>

            <!-- Page Number Buttons & Prev / Next Controls -->
            <div v-if="totalPages > 1" class="flex items-center gap-1.5">
              <!-- Prev Button -->
              <button 
                @click="prevPage" 
                :disabled="currentPage === 1"
                title="Previous Page"
                class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 disabled:opacity-30 disabled:hover:bg-slate-800 disabled:cursor-not-allowed text-white font-bold rounded-xl border border-slate-700 transition flex items-center gap-1">
                ← Prev
              </button>

              <!-- Numbered Page Pills: 1, 2, 3... -->
              <button 
                v-for="p in totalPages" 
                :key="p"
                @click="setPage(p)"
                class="w-8 h-8 rounded-xl font-mono text-xs font-black transition-all flex items-center justify-center border"
                :class="currentPage === p ? 'bg-brand-600 text-white border-brand-400 shadow-lg shadow-brand-500/30 ring-2 ring-brand-400/40' : 'bg-slate-800 text-slate-400 hover:text-white hover:bg-slate-700 border-slate-700'">
                {{ p }}
              </button>

              <!-- Next Button -->
              <button 
                @click="nextPage" 
                :disabled="currentPage === totalPages"
                title="Next Page"
                class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 disabled:opacity-30 disabled:hover:bg-slate-800 disabled:cursor-not-allowed text-white font-bold rounded-xl border border-slate-700 transition flex items-center gap-1">
                Next →
              </button>
            </div>
            <span v-else class="text-slate-500 text-[11px]">All waiting patients shown on 1 page</span>
          </div>
        </div>
      </div>

    </main>

    <!-- Bottom Ticker Bar -->
    <footer class="bg-brand-950/80 border border-brand-900/80 rounded-2xl py-3 px-6 flex flex-wrap items-center justify-between text-xs text-brand-200 gap-2">
      <div class="flex items-center gap-2">
        <Sparkles class="w-4 h-4 text-cyan-400 shrink-0" />
        <span><strong>HealthPulse Notice:</strong> Pharmacy Counter #3 and Pathology Collection Bay are open 24/7 on Ground Floor.</span>
      </div>
      <div class="font-bold text-white">Emergency Trauma: +1 (800) 456-7890 (Line 1)</div>
    </footer>

  </div>
</template>


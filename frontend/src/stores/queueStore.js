import { defineStore } from 'pinia';
import api from '../services/api';

export const useQueueStore = defineStore('queue', {
  state: () => ({
    currentlyCalling: [],
    waitingList: [],
    completedList: [],
    departments: [],
    selectedDepartment: null,
    totalToday: 0,
    lastCalledTokenId: null,
    audioAlertEnabled: true,
    pollingInterval: null,
    isFlashing: false,
    emergencyAlert: null,
  }),

  actions: {
    async fetchLiveQueue() {
      try {
        const params = {};
        if (this.selectedDepartment) {
          params.department_id = this.selectedDepartment;
        }

        const res = await api.get('/queue/live', { params });
        if (res.data.success) {
          const prevLatestId = this.lastCalledTokenId;
          this.currentlyCalling = res.data.currently_calling || [];
          this.waitingList = res.data.waiting_list || [];
          this.completedList = res.data.completed_list || [];
          this.totalToday = res.data.total_today || 0;
          this.departments = res.data.departments || [];

          if (this.currentlyCalling.length > 0) {
            const latest = this.currentlyCalling[0];
            // If new token called or first load
            if (prevLatestId !== null && prevLatestId !== latest.id) {
              this.lastCalledTokenId = latest.id;
              this.triggerCallAnnouncement(latest);
              this.flashScreen();
            } else if (prevLatestId === null) {
              this.lastCalledTokenId = latest.id;
            }
          }
        }
      } catch (err) {
        console.error('Failed to fetch live queue', err);
      }
    },

    playHospitalChime() {
      try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        // Reuse pre-warmed context if available, otherwise create new
        const ctx = (window._hmsAudioCtx && window._hmsAudioCtx.state !== 'closed')
          ? window._hmsAudioCtx
          : new AudioContext();
        window._hmsAudioCtx = ctx;

        // Resume context if it got suspended
        if (ctx.state === 'suspended') {
          ctx.resume().catch(() => {});
        }

        const playTone = (freq, start, duration) => {
          const osc = ctx.createOscillator();
          const gain = ctx.createGain();
          osc.type = 'sine';
          osc.frequency.setValueAtTime(freq, ctx.currentTime + start);
          gain.gain.setValueAtTime(0, ctx.currentTime + start);
          gain.gain.linearRampToValueAtTime(0.35, ctx.currentTime + start + 0.05);
          gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + start + duration);
          osc.connect(gain);
          gain.connect(ctx.destination);
          osc.start(ctx.currentTime + start);
          osc.stop(ctx.currentTime + start + duration);
        };

        // Hospital ding-dong chime
        playTone(523.25, 0,    0.4);   // C5
        playTone(659.25, 0.35, 0.6);   // E5
        playTone(783.99, 0.8,  0.5);   // G5
      } catch (e) {
        console.warn('Audio chime playback failed', e);
      }
    },

    triggerCallAnnouncement(token) {
      if (!this.audioAlertEnabled) return;
      
      // 1. Play Ding-Dong Chime
      this.playHospitalChime();

      // 2. Play Vocal Speech after small chime pause
      if ('speechSynthesis' in window) {
        setTimeout(() => {
          try {
            window.speechSynthesis.cancel(); // Stop any pending
            const text = `Token number ${token.token_number}. Patient ${token.patient_name}. Please proceed to ${token.counter_no}.`;
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.rate = 0.9;
            utterance.pitch = 1.05;
            utterance.lang = 'en-US';
            window.speechSynthesis.speak(utterance);
          } catch (e) {
            console.warn('Speech synthesis error', e);
          }
        }, 700);
      }
    },

    flashScreen() {
      this.isFlashing = true;
      setTimeout(() => {
        this.isFlashing = false;
      }, 1500);
    },

    startPolling(intervalMs = 3000) {
      this.fetchLiveQueue();
      if (!this.pollingInterval) {
        this.pollingInterval = setInterval(() => {
          this.fetchLiveQueue();
        }, intervalMs);
      }
    },

    stopPolling() {
      if (this.pollingInterval) {
        clearInterval(this.pollingInterval);
        this.pollingInterval = null;
      }
    },

    setDepartmentFilter(deptId) {
      this.selectedDepartment = deptId;
      this.fetchLiveQueue();
    }
  }
});

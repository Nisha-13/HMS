import { defineStore } from 'pinia';
import api from '../services/api';

export const useHospitalStore = defineStore('hospital', {
  state: () => ({
    departments: [],
    doctors: [],
    services: [],
    events: [],
    testimonials: [],
    stats: {
      doctors: 45,
      departments: 12,
      beds: 160,
      bed_occupancy_rate: 74.5,
      appointments_handled: 5400,
      patient_satisfaction: '99.4%',
      emergency_response_mins: '3 mins'
    },
    loading: false,
    selectedDepartment: null,
  }),

  actions: {
    async fetchAllPublicData() {
      this.loading = true;
      try {
        const [deptRes, docRes, srvRes, evRes, testRes, statsRes] = await Promise.all([
          api.get('/departments'),
          api.get('/doctors'),
          api.get('/services'),
          api.get('/events'),
          api.get('/testimonials'),
          api.get('/stats'),
        ]);

        if (deptRes.data.success) this.departments = deptRes.data.departments;
        if (docRes.data.success) this.doctors = docRes.data.doctors;
        if (srvRes.data.success) this.services = srvRes.data.services;
        if (evRes.data.success) this.events = evRes.data.events;
        if (testRes.data.success) this.testimonials = testRes.data.testimonials;
        if (statsRes.data.success) this.stats = statsRes.data.stats;
      } catch (err) {
        console.error('Error fetching hospital public data:', err);
      } finally {
        this.loading = false;
      }
    },

    async fetchPublicData() {
      return this.fetchAllPublicData();
    },

    async bookAppointment(payload) {
      try {
        const res = await api.post('/appointments/book', payload);
        return res.data;
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Booking failed. Please check inputs.'
        };
      }
    },

    async sendContactInquiry(payload) {
      try {
        const res = await api.post('/contact', payload);
        return res.data;
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Submission failed.'
        };
      }
    }
  }
});

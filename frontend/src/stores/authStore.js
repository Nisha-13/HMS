import { defineStore } from 'pinia';
import api from '../services/api';

const getStoredUser = () => {
  try {
    const raw = localStorage.getItem('hp_user');
    if (!raw || raw === 'undefined' || raw === 'null') return null;
    return JSON.parse(raw);
  } catch (e) {
    return null;
  }
};

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: getStoredUser(),
    token: localStorage.getItem('hp_token') || null,
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    role: (state) => state.user?.role || null,
    isAdmin: (state) => state.user?.role === 'admin',
    isDoctor: (state) => state.user?.role === 'doctor',
    isPatient: (state) => state.user?.role === 'patient',
    isReceptionist: (state) => state.user?.role === 'receptionist',
  },

  actions: {
    async login(email, password) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/auth/login', { email, password });
        if (response.data.success) {
          this.token = response.data.token;
          this.user = response.data.user;
          localStorage.setItem('hp_token', this.token);
          localStorage.setItem('hp_user', JSON.stringify(this.user));
          return { success: true, user: this.user };
        }
      } catch (err) {
        this.error = err.response?.data?.message || 'Login failed. Please check your credentials.';
        return { 
          success: false, 
          message: this.error,
          errors: err.response?.data?.errors || null
        };
      } finally {
        this.loading = false;
      }
    },

    async register(payload) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/auth/register', payload);
        if (response.data.success) {
          this.token = response.data.token;
          this.user = response.data.user;
          localStorage.setItem('hp_token', this.token);
          localStorage.setItem('hp_user', JSON.stringify(this.user));
          return { success: true, user: this.user };
        }
      } catch (err) {
        this.error = err.response?.data?.message || 'Registration failed.';
        return { 
          success: false, 
          message: this.error,
          errors: err.response?.data?.errors || null
        };
      } finally {
        this.loading = false;
      }
    },

    async updateAvatar(avatar) {
      this.loading = true;
      try {
        const response = await api.post('/auth/avatar', { avatar });
        if (response.data.success) {
          this.user = { ...this.user, ...response.data.user, avatar: response.data.avatar };
          localStorage.setItem('hp_user', JSON.stringify(this.user));
          return { success: true, avatar: response.data.avatar, user: this.user };
        }
        return { success: false, message: response.data?.message || 'Failed to update avatar' };
      } catch (err) {
        return { success: false, message: err.response?.data?.message || 'Failed to update avatar' };
      } finally {
        this.loading = false;
      }
    },

    async fetchMe() {
      if (!this.token) return;
      try {
        const response = await api.get('/auth/me');
        if (response.data.success) {
          this.user = response.data.user;
          localStorage.setItem('hp_user', JSON.stringify(this.user));
        }
      } catch (err) {
        this.logout();
      }
    },

    logout() {
      if (this.token) {
        api.post('/auth/logout').catch(() => {});
      }
      this.user = null;
      this.token = null;
      localStorage.removeItem('hp_token');
      localStorage.removeItem('hp_user');
    }
  }
});

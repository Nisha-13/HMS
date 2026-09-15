<script setup>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/authStore';
import { 
  HeartPulse, Lock, Mail, User, ShieldCheck, 
  UserCheck, AlertCircle, ArrowRight,
  Eye, EyeOff, Upload, Camera, Check, X, Sparkles
} from 'lucide-vue-next';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const isRegister = ref(false);
const email = ref('');
const password = ref('');
const confirmPassword = ref('');
const showPassword = ref(false);
const showConfirmPassword = ref(false);
const name = ref('');
const phone = ref('');
const gender = ref('male');
const bloodGroup = ref('O+');
const avatarPreview = ref('');
const selectedAvatarPreset = ref('');
const errorMsg = ref('');
const fieldErrors = ref({});

const clearFieldError = (field) => {
  if (fieldErrors.value[field]) {
    delete fieldErrors.value[field];
  }
};

const getFieldError = (field) => {
  const err = fieldErrors.value[field];
  if (!err) return '';
  return Array.isArray(err) ? err[0] : err;
};

// Auto-format Pakistani mobile number: 03XX-XXXXXXX
const handleRegPhoneInput = (e) => {
  let digits = e.target.value.replace(/\D/g, '').slice(0, 11);
  let formatted = digits;
  if (digits.length > 4) {
    formatted = `${digits.slice(0, 4)}-${digits.slice(4)}`;
  }
  phone.value = formatted;
  clearFieldError('phone');
};

// Full client-side validation for registration form
const validateRegForm = () => {
  const errors = {};

  // Name: required, min 3, letters only
  const nameVal = name.value.trim();
  if (!nameVal) {
    errors.name = 'Full name is required.';
  } else if (nameVal.length < 3) {
    errors.name = 'Name must be at least 3 characters.';
  } else if (/^\d+$/.test(nameVal)) {
    errors.name = 'Name cannot be all numbers. Please enter your full name.';
  } else if (!/^[a-zA-Z\s.\'-]+$/.test(nameVal)) {
    errors.name = 'Name should only contain letters, spaces, or . \' - characters.';
  }

  // Email: required + basic format
  const emailVal = email.value.trim();
  if (!emailVal) {
    errors.email = 'Email address is required.';
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
    errors.email = 'Please enter a valid email address (e.g. name@example.com).';
  }

  // Phone: optional but if entered must be Pakistani format
  const phoneVal = phone.value.trim();
  if (phoneVal) {
    const digits = phoneVal.replace(/\D/g, '');
    if (digits.length !== 11) {
      errors.phone = 'Phone must be 11 digits (e.g. 0300-1234567).';
    } else if (!digits.startsWith('03')) {
      errors.phone = 'Pakistani mobile must start with 03 (e.g. 0300-1234567).';
    }
  }

  // Password: required, min 8 chars, at least one letter and one number
  const passVal = password.value;
  if (!passVal) {
    errors.password = 'Password is required.';
  } else if (passVal.length < 8) {
    errors.password = 'Password must be at least 8 characters long.';
  } else if (!/[a-zA-Z]/.test(passVal)) {
    errors.password = 'Password must contain at least one letter.';
  } else if (!/[0-9]/.test(passVal)) {
    errors.password = 'Password must contain at least one number.';
  }

  // Confirm password
  if (!confirmPassword.value) {
    errors.confirmPassword = 'Please confirm your password.';
  } else if (password.value !== confirmPassword.value) {
    errors.confirmPassword = 'Passwords do not match. Please re-enter.';
  }

  return errors;
};

// Login validation
const validateLoginForm = () => {
  const errors = {};
  const emailVal = email.value.trim();
  if (!emailVal) {
    errors.email = 'Please enter your email address.';
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
    errors.email = 'Please enter a valid email address.';
  }
  if (!password.value) {
    errors.password = 'Please enter your password.';
  }
  return errors;
};

const avatarPresets = [
  { id: 'p1', label: 'Default Patient', url: '/assets/images/default-patient.svg' },
  { id: 'p2', label: 'Male Profile', url: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80' },
  { id: 'p3', label: 'Female Profile', url: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&auto=format&fit=crop&q=80' },
  { id: 'p4', label: 'Senior Patient', url: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80' },
];

const handleFileUpload = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  if (file.size > 3 * 1024 * 1024) {
    errorMsg.value = 'Image size should be less than 3MB';
    return;
  }

  const reader = new FileReader();
  reader.onload = (event) => {
    avatarPreview.value = event.target.result;
    selectedAvatarPreset.value = '';
  };
  reader.readAsDataURL(file);
  e.target.value = '';
};

const selectPreset = (preset) => {
  selectedAvatarPreset.value = preset.id;
  avatarPreview.value = preset.url;
};

const removeAvatar = () => {
  avatarPreview.value = '';
  selectedAvatarPreset.value = '';
};

const handleSubmit = async () => {
  errorMsg.value = '';
  fieldErrors.value = {};

  if (isRegister.value) {
    // Run full client-side validation
    const errors = validateRegForm();
    if (Object.keys(errors).length > 0) {
      fieldErrors.value = errors;
      return;
    }

    const res = await authStore.register({
      name: name.value.trim(),
      email: email.value.trim(),
      phone: phone.value.trim() || null,
      password: password.value,
      gender: gender.value,
      blood_group: bloodGroup.value,
      avatar: avatarPreview.value || 'assets/images/default-patient.svg',
    });

    if (res && res.success) {
      router.push('/patient');
    } else if (res) {
      if (res.errors) {
        // Map backend errors to fieldErrors
        const mapped = {};
        Object.keys(res.errors).forEach(k => {
          mapped[k] = Array.isArray(res.errors[k]) ? res.errors[k][0] : res.errors[k];
        });
        fieldErrors.value = mapped;
      } else {
        errorMsg.value = res.message || 'Registration failed. Please try again.';
      }
    }
  } else {
    // Login validation
    const errors = validateLoginForm();
    if (Object.keys(errors).length > 0) {
      fieldErrors.value = errors;
      return;
    }

    const res = await authStore.login(email.value.trim(), password.value);
    if (res && res.success) {
      if (res.user.role === 'admin') router.push('/admin');
      else if (res.user.role === 'doctor') router.push('/doctor');
      else if (res.user.role === 'receptionist') router.push('/reception');
      else router.push('/patient');
    } else if (res) {
      if (res.errors) {
        const mapped = {};
        Object.keys(res.errors).forEach(k => {
          mapped[k] = Array.isArray(res.errors[k]) ? res.errors[k][0] : res.errors[k];
        });
        fieldErrors.value = mapped;
      } else {
        errorMsg.value = res.message || 'Login failed. Please check your credentials.';
      }
    }
  }
};
</script>

<template>
  <div class="min-h-screen bg-slate-100 flex items-center justify-center p-4 py-12">
    <div class="max-w-lg w-full">
      
      <!-- Top Brand Logo -->
      <div class="text-center mb-8">
        <router-link to="/" class="inline-flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-brand-600 flex items-center justify-center text-white shadow-lg shadow-brand-500/30 hover:scale-105 transition-transform">
            <HeartPulse class="w-7 h-7 animate-pulse" />
          </div>
          <div class="text-left">
            <span class="text-2xl font-black text-slate-900">HealthPulse</span>
            <span class="text-xs font-bold uppercase tracking-wider text-brand-600 block">Hospital Portal</span>
          </div>
        </router-link>
      </div>

      <!-- Auth Form Card -->
      <div class="bg-white rounded-3xl p-7 sm:p-9 shadow-xl border border-slate-100">
        
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
          <div>
            <h2 class="text-xl font-extrabold text-slate-900">
              {{ isRegister ? 'Patient Registration' : 'Hospital Portal Login' }}
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">
              {{ isRegister ? 'Create your patient health account' : 'Access your smart clinical dashboard' }}
            </p>
          </div>
          <button 
            type="button"
            @click="isRegister = !isRegister; errorMsg = ''; fieldErrors = {}"
            class="text-xs font-bold text-brand-600 hover:text-brand-700 px-3 py-1.5 rounded-lg bg-brand-50 hover:bg-brand-100 transition cursor-pointer">
            {{ isRegister ? 'Back to Login' : 'Create Account' }}
          </button>
        </div>

        <div v-if="errorMsg" class="mb-5 p-3.5 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl flex items-center gap-2 animate-shake">
          <AlertCircle class="w-4 h-4 flex-shrink-0" />
          <span>{{ errorMsg }}</span>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4" novalidate>
          
          <!-- REGISTRATION EXTRA FIELDS -->
          <template v-if="isRegister">
            
            <!-- Patient Profile Photo Upload & Presets -->
            <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4 space-y-3">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                Profile Photo (Optional)
              </label>

              <div class="flex items-center gap-4">
                <div class="relative group">
                  <img 
                    :src="avatarPreview || '/assets/images/default-patient.svg'" 
                    class="w-16 h-16 rounded-2xl object-cover border-2 border-brand-500 shadow-sm bg-white"
                  />
                  <button 
                    v-if="avatarPreview"
                    type="button" 
                    @click="removeAvatar"
                    class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center text-xs shadow hover:bg-red-600 transition">
                    <X class="w-3 h-3" />
                  </button>
                </div>

                <div class="flex-1 space-y-1">
                  <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-slate-200 hover:border-brand-500 text-xs font-semibold text-slate-700 cursor-pointer shadow-sm hover:shadow transition">
                    <Upload class="w-3.5 h-3.5 text-brand-600" />
                    <span>Upload Custom Photo</span>
                    <input type="file" accept="image/*" class="hidden" @change="handleFileUpload" />
                  </label>
                  <p class="text-[11px] text-slate-400">JPG, PNG or WEBP up to 3MB</p>
                </div>
              </div>

              <!-- Quick Presets -->
              <div>
                <span class="text-[11px] font-semibold text-slate-500 block mb-1.5">Or Choose an Avatar:</span>
                <div class="grid grid-cols-4 gap-2">
                  <button 
                    v-for="p in avatarPresets" 
                    :key="p.id"
                    type="button"
                    @click="selectPreset(p)"
                    class="p-1.5 rounded-xl border flex flex-col items-center gap-1 transition cursor-pointer hover:bg-white"
                    :class="selectedAvatarPreset === p.id ? 'border-brand-600 bg-brand-50 ring-2 ring-brand-500/20' : 'border-slate-200 bg-white/60'">
                    <img :src="p.url" class="w-8 h-8 rounded-lg object-cover" />
                    <span class="text-[9px] font-bold text-slate-600 truncate w-full text-center">{{ p.label }}</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Full Name -->
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                Full Name <span class="text-red-500">*</span>
              </label>
              <input 
                type="text" 
                v-model="name"
                @input="clearFieldError('name')"
                placeholder="e.g. Emma Watson"
                class="w-full rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition"
                :class="getFieldError('name') ? 'border border-red-400 bg-red-50/20 ring-1 ring-red-300 focus:ring-red-400' : 'bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-brand-500'"
              />
              <p v-if="getFieldError('name')" class="mt-1.5 text-xs text-red-600 flex items-center gap-1 animate-in fade-in slide-in-from-top-1">
                <AlertCircle class="w-3.5 h-3.5 flex-shrink-0" />
                <span>{{ getFieldError('name') }}</span>
              </p>
            </div>

            <!-- Phone, Gender & Blood Group -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                  Phone Number
                  <span class="text-slate-400 font-normal normal-case ml-1">(optional)</span>
                </label>
                <input 
                  type="tel" 
                  v-model="phone"
                  @input="handleRegPhoneInput"
                  placeholder="0300-1234567"
                  maxlength="12"
                  class="w-full rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:bg-white transition"
                  :class="getFieldError('phone') ? 'border border-red-400 bg-red-50/20 ring-1 ring-red-300 focus:ring-red-400' : 'bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-brand-500'"
                />
                <p v-if="getFieldError('phone')" class="mt-1.5 text-xs text-red-600 flex items-center gap-1 animate-in fade-in slide-in-from-top-1">
                  <AlertCircle class="w-3.5 h-3.5 flex-shrink-0" />
                  <span>{{ getFieldError('phone') }}</span>
                </p>
                <p v-else class="mt-1 text-[10px] text-slate-400">Pakistani format: 03XX-XXXXXXX</p>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Gender</label>
                <select 
                  v-model="gender"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="other">Other</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Blood Group</label>
                <select 
                  v-model="bloodGroup"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                  <option value="A+">A+</option>
                  <option value="A-">A-</option>
                  <option value="B+">B+</option>
                  <option value="B-">B-</option>
                  <option value="O+">O+</option>
                  <option value="O-">O-</option>
                  <option value="AB+">AB+</option>
                  <option value="AB-">AB-</option>
                </select>
              </div>
            </div>
          </template>

          <!-- Email Address -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Email Address <span class="text-red-500">*</span>
            </label>
            <input 
              type="email" 
              v-model="email"
              @input="clearFieldError('email')"
              placeholder="name@healthpulse.com"
              class="w-full rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition"
              :class="getFieldError('email') ? 'border border-red-400 bg-red-50/20 ring-1 ring-red-300 focus:ring-red-400' : 'bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-brand-500'"
            />
            <p v-if="getFieldError('email')" class="mt-1.5 text-xs text-red-600 flex items-center gap-1 animate-in fade-in slide-in-from-top-1">
              <AlertCircle class="w-3.5 h-3.5 flex-shrink-0" />
              <span>{{ getFieldError('email') }}</span>
            </p>
          </div>

          <!-- Password Field with Interactive Eye Icon -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Password <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input 
                :type="showPassword ? 'text' : 'password'" 
                v-model="password"
                @input="clearFieldError('password')"
                placeholder="••••••••"
                class="w-full rounded-xl pl-4 pr-11 py-2.5 text-sm focus:outline-none focus:bg-white transition"
                :class="getFieldError('password') ? 'border border-red-400 bg-red-50/20 ring-1 ring-red-300 focus:ring-red-400' : 'bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-brand-500'"
              />
              <button 
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-brand-600 hover:bg-slate-100 focus:outline-none transition cursor-pointer"
                :title="showPassword ? 'Hide password' : 'Show password'"
                aria-label="Toggle password visibility">
                <EyeOff v-if="showPassword" class="w-4 h-4 text-brand-600" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
            <p v-if="getFieldError('password')" class="mt-1.5 text-xs text-red-600 flex items-center gap-1 animate-in fade-in slide-in-from-top-1">
              <AlertCircle class="w-3.5 h-3.5 flex-shrink-0" />
              <span>{{ getFieldError('password') }}</span>
            </p>
            <p v-else-if="isRegister" class="mt-1 text-[10px] text-slate-400">Min 8 characters · must include letters and numbers</p>
          </div>

          <!-- Confirm Password (Register mode only) -->
          <div v-if="isRegister">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Confirm Password <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input 
                :type="showConfirmPassword ? 'text' : 'password'" 
                v-model="confirmPassword"
                @input="clearFieldError('confirmPassword')"
                placeholder="••••••••"
                class="w-full rounded-xl pl-4 pr-11 py-2.5 text-sm focus:outline-none focus:bg-white transition"
                :class="getFieldError('confirmPassword') ? 'border border-red-400 bg-red-50/20 ring-1 ring-red-300 focus:ring-red-400' : 'bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-brand-500'"
              />
              <button 
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-brand-600 hover:bg-slate-100 focus:outline-none transition cursor-pointer"
                :title="showConfirmPassword ? 'Hide password' : 'Show password'"
                aria-label="Toggle password visibility">
                <EyeOff v-if="showConfirmPassword" class="w-4 h-4 text-brand-600" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
            <p v-if="getFieldError('confirmPassword')" class="mt-1.5 text-xs text-red-600 flex items-center gap-1 animate-in fade-in slide-in-from-top-1">
              <AlertCircle class="w-3.5 h-3.5 flex-shrink-0" />
              <span>{{ getFieldError('confirmPassword') }}</span>
            </p>
          </div>

          <button 
            type="submit" 
            :disabled="authStore.loading"
            class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs uppercase tracking-wider py-3.5 rounded-xl shadow-lg shadow-brand-500/25 transition-all disabled:opacity-50 mt-2 cursor-pointer">
            <span v-if="authStore.loading">Authenticating...</span>
            <span v-else>{{ isRegister ? 'Complete Registration & Access Portal' : 'Login To Dashboard →' }}</span>
          </button>
        </form>

        <!-- Enterprise Security & Compliance Badge (Production Ready) -->
        <div v-if="!isRegister" class="mt-8 pt-6 border-t border-slate-100">
          <div class="flex items-center justify-center gap-2 text-slate-500">
            <ShieldCheck class="w-4 h-4 text-emerald-600 flex-shrink-0" />
            <span class="text-[11px] font-bold text-slate-700">
              HIPAA & GDPR Compliant Medical Portal
            </span>
          </div>
          <p class="text-[10px] text-slate-400 text-center mt-1 font-medium">
            256-Bit SSL Encrypted • Authorized Staff & Registered Patient Access Only
          </p>
        </div>

      </div>

    </div>
  </div>
</template>

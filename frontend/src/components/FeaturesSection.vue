<script setup>
import { 
  ShieldCheck, Cpu, Clock, HeartPulse, UserCheck, 
  BedDouble, Sparkles, Building2, FlaskConical, Award
} from 'lucide-vue-next';

const features = [
  {
    icon: HeartPulse,
    title: 'Advanced Robotic Surgery',
    desc: 'Cutting-edge da Vinci robotic surgical suites delivering ultra-precise procedures with minimal recovery downtime.',
    tag: 'Next-Gen Tech'
  },
  {
    icon: Clock,
    title: '24/7 Level-1 Emergency & Trauma',
    desc: 'Fully equipped trauma resuscitation unit with immediate catheterization and critical surgery standby.',
    tag: 'Round-The-Clock'
  },
  {
    icon: Cpu,
    title: 'Intelligent EMR & Digital OPD',
    desc: 'Seamless electronic medical records, zero-wait OPD queue tokens, and live consultation status tracking.',
    tag: 'Smart Hospital'
  },
  {
    icon: Building2,
    title: 'Comprehensive Diagnostic Suites',
    desc: '3-Tesla quiet MRI, 128-Slice cardiac CT, high-frequency digital ultrasound, and automated pathology lab.',
    tag: 'Precision Diagnostics'
  },
  {
    icon: BedDouble,
    title: 'Smart ICU & Inpatient Care',
    desc: 'High-dependency units featuring continuous telemetry monitoring and dedicated 1-on-1 critical nursing.',
    tag: '160+ Beds'
  },
  {
    icon: Award,
    title: 'International Board Specialists',
    desc: 'Over 50+ board-certified MDs, surgeons, and department heads with global clinical fellowship training.',
    tag: 'Excellence'
  }
];
</script>

<template>
  <section class="py-20 bg-slate-50 relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- Section Header -->
      <div class="text-center max-w-3xl mx-auto mb-16">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-100 text-brand-700 text-xs font-bold uppercase tracking-wider mb-3">
          <Sparkles class="w-3.5 h-3.5" />
          Best Hospital In Town
        </div>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
          When It Comes To Healthcare, <br />
          <span class="text-brand-600">We Set The Standard.</span>
        </h2>
        <p class="mt-4 text-base text-slate-600 leading-relaxed">
          HealthPulse combines medical expertise, compassionate patient care, and intelligent hospital technology to deliver unmatched clinical outcomes.
        </p>
      </div>

      <!-- Feature Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div 
          v-for="(f, idx) in features" 
          :key="idx"
          class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group relative">
          
          <div class="flex items-center justify-between mb-6">
            <div class="w-14 h-14 rounded-2xl bg-brand-50 text-brand-600 group-hover:bg-brand-600 group-hover:text-white flex items-center justify-center transition-colors duration-300 shadow-sm">
              <component :is="f.icon" class="w-7 h-7" />
            </div>
            <span class="text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 group-hover:bg-brand-50 group-hover:text-brand-700 transition-colors">
              {{ f.tag }}
            </span>
          </div>

          <h3 class="text-xl font-bold text-slate-900 mb-3 group-hover:text-brand-600 transition-colors">
            {{ f.title }}
          </h3>

          <p class="text-sm text-slate-600 leading-relaxed">
            {{ f.desc }}
          </p>
        </div>
      </div>

    </div>
  </section>
</template>

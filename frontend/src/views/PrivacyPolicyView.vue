<script setup>
import { 
  ShieldCheck, Lock, Eye, FileText, Database, 
  CheckCircle2, AlertCircle, Sparkles, Building2, PhoneCall 
} from 'lucide-vue-next';
</script>

<template>
  <div class="min-h-screen bg-slate-50 py-16">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
      
      <!-- Page Header -->
      <div class="text-center space-y-3 max-w-3xl mx-auto">
        <span class="text-xs font-bold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1.5 rounded-full border border-brand-200">
          Trust & Compliance
        </span>
        <h1 class="text-3xl sm:text-5xl font-extrabold text-slate-900 tracking-tight">
          Privacy Policy & Patient Data Security
        </h1>
        <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
          At HealthPulse Hospital, we are dedicated to maintaining the highest ethical and regulatory standards for safeguarding patient health records, diagnostic data, and personal privacy.
        </p>
        <div class="text-xs text-slate-400">
          Last Updated & Certified: September 2026 • HIPAA & GDPR Compliant
        </div>
      </div>

      <!-- Trust Badges Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm flex items-start gap-4">
          <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
            <Lock class="w-6 h-6" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-slate-900">End-to-End Encryption</h3>
            <p class="text-xs text-slate-500 mt-1">256-bit AES database encryption on all clinical records & prescriptions.</p>
          </div>
        </div>

        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm flex items-start gap-4">
          <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
            <ShieldCheck class="w-6 h-6" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-slate-900">HIPAA Compliant</h3>
            <p class="text-xs text-slate-500 mt-1">Protected health information (PHI) is strictly guarded and confidential.</p>
          </div>
        </div>

        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm flex items-start gap-4">
          <div class="w-12 h-12 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0">
            <Eye class="w-6 h-6" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-slate-900">Zero Data Selling</h3>
            <p class="text-xs text-slate-500 mt-1">Your medical data is never sold, leased, or shared with commercial marketers.</p>
          </div>
        </div>
      </div>

      <!-- Policy Content Sections -->
      <div class="bg-white rounded-3xl p-8 sm:p-12 border border-slate-100 shadow-sm space-y-10 text-slate-700 leading-relaxed text-sm">
        
        <!-- Section 1 -->
        <div class="space-y-3">
          <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2.5">
            <span class="w-7 h-7 rounded-lg bg-brand-50 text-brand-600 text-xs font-bold flex items-center justify-center">1</span>
            Information We Collect
          </h2>
          <p>
            When you register for consultations, free health screening camps, or emergency admissions at HealthPulse Hospital, we collect essential demographic and clinical data including:
          </p>
          <ul class="list-disc list-inside space-y-1 text-slate-600 pl-2">
            <li><strong>Patient Identification:</strong> Full Name, Date of Birth, Gender, National ID / CNIC number, and contact details.</li>
            <li><strong>Clinical History:</strong> Symptoms, past medical history, known drug allergies, prescribed medications, and laboratory test results.</li>
            <li><strong>Financial Details:</strong> OPD consultation billing, insurance coverage eligibility, and payment transaction receipts.</li>
          </ul>
        </div>

        <!-- Section 2 -->
        <div class="space-y-3">
          <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2.5">
            <span class="w-7 h-7 rounded-lg bg-brand-50 text-brand-600 text-xs font-bold flex items-center justify-center">2</span>
            How We Use Your Medical Information
          </h2>
          <p>
            Your information is used strictly by licensed hospital healthcare professionals for:
          </p>
          <ul class="list-disc list-inside space-y-1 text-slate-600 pl-2">
            <li>Providing accurate medical diagnoses, treatment planning, and surgical interventions.</li>
            <li>Coordinating OPD queues, issuing digital consultation tokens, and dispensing community health passes.</li>
            <li>Managing pharmacy medication dispensing and preventing drug-drug interaction contraindications.</li>
            <li>Hospital clinical audits, quality assurance, and infection control monitoring.</li>
          </ul>
        </div>

        <!-- Section 3 -->
        <div class="space-y-3">
          <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2.5">
            <span class="w-7 h-7 rounded-lg bg-brand-50 text-brand-600 text-xs font-bold flex items-center justify-center">3</span>
            Patient Rights & Data Control
          </h2>
          <p>
            As a valued patient of HealthPulse Hospital, you retain complete statutory rights under health privacy regulations:
          </p>
          <ul class="list-disc list-inside space-y-1 text-slate-600 pl-2">
            <li><strong>Access:</strong> You may request a complete digital or physical copy of your medical records and discharge summaries at any time.</li>
            <li><strong>Confidentiality:</strong> Only attending physicians, consulting specialists, and assigned nurses can view your case history.</li>
            <li><strong>Corrections:</strong> You have the right to request amendments or updates to outdated contact or demographic records.</li>
          </ul>
        </div>

        <!-- Section 4 -->
        <div class="space-y-3">
          <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2.5">
            <span class="w-7 h-7 rounded-lg bg-brand-50 text-brand-600 text-xs font-bold flex items-center justify-center">4</span>
            Data Retention & Security Protocol
          </h2>
          <p>
            HealthPulse Hospital utilizes multi-tiered security firewalls, TLS 1.3 encrypted data transmission, and automated hourly database backups. Patient medical records are retained in compliance with healthcare statutory guidelines and destroyed via secure electronic shredding after the regulatory lifecycle.
          </p>
        </div>

        <!-- Contact Support Box -->
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div>
            <h4 class="font-bold text-slate-900 text-base">Have Questions About Patient Privacy?</h4>
            <p class="text-xs text-slate-500 mt-0.5">Contact our Data Protection & Patient Relations Officer.</p>
          </div>
          <router-link 
            to="/contact" 
            class="bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs uppercase tracking-wider py-3 px-6 rounded-xl shadow-md transition whitespace-nowrap">
            Contact Hospital Desk →
          </router-link>
        </div>

      </div>

    </div>
  </div>
</template>

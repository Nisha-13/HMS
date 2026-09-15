<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HealthPulse HMS — Backend API Explorer</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
    pre, code { font-family: 'JetBrains Mono', monospace; }
  </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
  
  <!-- Top Banner -->
  <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-md sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-500 to-teal-400 flex items-center justify-center text-white font-black text-xl shadow-lg shadow-sky-500/20">
          +
        </div>
        <div>
          <h1 class="text-lg font-bold text-white tracking-tight flex items-center gap-2">
            HealthPulse HMS <span class="text-xs px-2 py-0.5 rounded-md bg-sky-500/20 text-sky-400 border border-sky-500/30">API v1.0</span>
          </h1>
          <p class="text-xs text-slate-400">Laravel 11 RESTful Backend Engine</p>
        </div>
      </div>

      <div class="flex items-center gap-3 text-xs">
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-medium">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          API Online & Serving
        </span>
        <a href="http://localhost:5173" target="_blank" class="px-4 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-500 text-white font-semibold transition shadow-md shadow-sky-600/30">
          Open Vue Frontend ↗
        </a>
      </div>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-4 sm:px-6 py-8 space-y-8">

    <!-- Quick Status Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Server Environment</span>
        <div class="text-base font-bold text-white mt-1">PHP {{ phpversion() }}</div>
        <span class="text-xs text-emerald-400 mt-0.5 block">Laravel 11.x Core</span>
      </div>
      <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">API Base URL</span>
        <div class="text-base font-bold text-sky-400 mt-1 font-mono text-xs">http://127.0.0.1:8000/api/v1</div>
        <span class="text-xs text-slate-400 mt-0.5 block">RESTful JSON</span>
      </div>
      <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Authentication</span>
        <div class="text-base font-bold text-teal-400 mt-1">Laravel Sanctum</div>
        <span class="text-xs text-slate-400 mt-0.5 block">Bearer Token Header</span>
      </div>
      <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">CORS Support</span>
        <div class="text-base font-bold text-purple-400 mt-1">Enabled (Port 5173)</div>
        <span class="text-xs text-slate-400 mt-0.5 block">Frontend SPA Allowed</span>
      </div>
    </div>

    <!-- Live Interactive API Tester -->
    <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-4">
        <div>
          <h2 class="text-base font-bold text-white flex items-center gap-2">
            <span>⚡</span> 1-Click Interactive API Tester
          </h2>
          <p class="text-xs text-slate-400">Click any public endpoint below to instantly test the live response</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button onclick="testEndpoint('/api/v1/stats')" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-sky-300 transition cursor-pointer">
            GET /stats
          </button>
          <button onclick="testEndpoint('/api/v1/departments')" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-sky-300 transition cursor-pointer">
            GET /departments
          </button>
          <button onclick="testEndpoint('/api/v1/doctors')" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-sky-300 transition cursor-pointer">
            GET /doctors
          </button>
          <button onclick="testEndpoint('/api/v1/services')" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-sky-300 transition cursor-pointer">
            GET /services
          </button>
          <button onclick="testEndpoint('/api/v1/events')" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-sky-300 transition cursor-pointer">
            GET /events
          </button>
          <button onclick="testEndpoint('/api/v1/queue/live')" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-teal-300 transition cursor-pointer">
            GET /queue/live
          </button>
        </div>
      </div>

      <!-- Result Box -->
      <div>
        <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
          <span>Active Request: <code id="activeEndpoint" class="text-sky-400 font-bold">/api/v1/stats</code></span>
          <span id="resStatus" class="text-emerald-400 font-bold">Ready</span>
        </div>
        <div class="relative">
          <pre id="jsonViewer" class="p-4 bg-slate-950 border border-slate-800 rounded-2xl text-xs text-emerald-300 max-h-72 overflow-y-auto font-mono">// Click any button above or hit 'Test /stats' to load live JSON</pre>
        </div>
      </div>
    </div>

    <!-- Complete Endpoints Reference Table -->
    <div class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800 space-y-6">
      <h2 class="text-base font-bold text-white flex items-center gap-2">
        <span>📖</span> Comprehensive HMS API Endpoints Directory
      </h2>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="text-slate-400 border-b border-slate-800">
              <th class="pb-3 uppercase tracking-wider font-semibold">Method</th>
              <th class="pb-3 uppercase tracking-wider font-semibold">Endpoint</th>
              <th class="pb-3 uppercase tracking-wider font-semibold">Access</th>
              <th class="pb-3 uppercase tracking-wider font-semibold">Description</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 font-mono">
            <tr>
              <td class="py-2.5"><span class="px-2 py-0.5 rounded bg-sky-500/20 text-sky-400 font-bold">GET</span></td>
              <td class="py-2.5 text-white"><a href="/api/v1/stats" target="_blank" class="hover:underline">/api/v1/stats</a></td>
              <td class="py-2.5 font-sans"><span class="text-slate-400">Public</span></td>
              <td class="py-2.5 font-sans text-slate-300">Hospital KPIs & overview stats</td>
            </tr>
            <tr>
              <td class="py-2.5"><span class="px-2 py-0.5 rounded bg-sky-500/20 text-sky-400 font-bold">GET</span></td>
              <td class="py-2.5 text-white"><a href="/api/v1/doctors" target="_blank" class="hover:underline">/api/v1/doctors</a></td>
              <td class="py-2.5 font-sans"><span class="text-slate-400">Public</span></td>
              <td class="py-2.5 font-sans text-slate-300">Doctors catalog with department & fees</td>
            </tr>
            <tr>
              <td class="py-2.5"><span class="px-2 py-0.5 rounded bg-sky-500/20 text-sky-400 font-bold">GET</span></td>
              <td class="py-2.5 text-white"><a href="/api/v1/departments" target="_blank" class="hover:underline">/api/v1/departments</a></td>
              <td class="py-2.5 font-sans"><span class="text-slate-400">Public</span></td>
              <td class="py-2.5 font-sans text-slate-300">Hospital clinical departments</td>
            </tr>
            <tr>
              <td class="py-2.5"><span class="px-2 py-0.5 rounded bg-sky-500/20 text-sky-400 font-bold">GET</span></td>
              <td class="py-2.5 text-white"><a href="/api/v1/services" target="_blank" class="hover:underline">/api/v1/services</a></td>
              <td class="py-2.5 font-sans"><span class="text-slate-400">Public</span></td>
              <td class="py-2.5 font-sans text-slate-300">Hospital diagnostic & treatment services</td>
            </tr>
            <tr>
              <td class="py-2.5"><span class="px-2 py-0.5 rounded bg-sky-500/20 text-sky-400 font-bold">GET</span></td>
              <td class="py-2.5 text-white"><a href="/api/v1/events" target="_blank" class="hover:underline">/api/v1/events</a></td>
              <td class="py-2.5 font-sans"><span class="text-slate-400">Public</span></td>
              <td class="py-2.5 font-sans text-slate-300">Health camps and outreach events</td>
            </tr>
            <tr>
              <td class="py-2.5"><span class="px-2 py-0.5 rounded bg-sky-500/20 text-sky-400 font-bold">GET</span></td>
              <td class="py-2.5 text-white"><a href="/api/v1/queue/live" target="_blank" class="hover:underline">/api/v1/queue/live</a></td>
              <td class="py-2.5 font-sans"><span class="text-slate-400">Public</span></td>
              <td class="py-2.5 font-sans text-slate-300">Live OPD TV queue screen display feed</td>
            </tr>
            <tr>
              <td class="py-2.5"><span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span></td>
              <td class="py-2.5 text-white">/api/v1/auth/register</td>
              <td class="py-2.5 font-sans"><span class="text-slate-400">Public</span></td>
              <td class="py-2.5 font-sans text-slate-300">Patient registration with custom avatar upload</td>
            </tr>
            <tr>
              <td class="py-2.5"><span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span></td>
              <td class="py-2.5 text-white">/api/v1/auth/login</td>
              <td class="py-2.5 font-sans"><span class="text-slate-400">Public</span></td>
              <td class="py-2.5 font-sans text-slate-300">Role-based login (Admin, Doctor, Patient, Receptionist)</td>
            </tr>
            <tr>
              <td class="py-2.5"><span class="px-2 py-0.5 rounded bg-purple-500/20 text-purple-400 font-bold">POST</span></td>
              <td class="py-2.5 text-white">/api/v1/admin/doctors</td>
              <td class="py-2.5 font-sans"><span class="text-purple-300">Admin Sanctum</span></td>
              <td class="py-2.5 font-sans text-slate-300">Register new specialist doctor to faculty</td>
            </tr>
            <tr>
              <td class="py-2.5"><span class="px-2 py-0.5 rounded bg-purple-500/20 text-purple-400 font-bold">POST</span></td>
              <td class="py-2.5 text-white">/api/v1/admin/departments</td>
              <td class="py-2.5 font-sans"><span class="text-purple-300">Admin Sanctum</span></td>
              <td class="py-2.5 font-sans text-slate-300">Create hospital medical department</td>
            </tr>
            <tr>
              <td class="py-2.5"><span class="px-2 py-0.5 rounded bg-purple-500/20 text-purple-400 font-bold">POST</span></td>
              <td class="py-2.5 text-white">/api/v1/admin/services</td>
              <td class="py-2.5 font-sans"><span class="text-purple-300">Admin Sanctum</span></td>
              <td class="py-2.5 font-sans text-slate-300">Create hospital service / facility</td>
            </tr>
            <tr>
              <td class="py-2.5"><span class="px-2 py-0.5 rounded bg-purple-500/20 text-purple-400 font-bold">POST</span></td>
              <td class="py-2.5 text-white">/api/v1/admin/events</td>
              <td class="py-2.5 font-sans"><span class="text-purple-300">Admin Sanctum</span></td>
              <td class="py-2.5 font-sans text-slate-300">Schedule health camp or event</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </main>

  <script>
    async function testEndpoint(url) {
      document.getElementById('activeEndpoint').innerText = url;
      document.getElementById('resStatus').innerText = 'Loading...';
      document.getElementById('resStatus').className = 'text-amber-400 font-bold';
      document.getElementById('jsonViewer').innerText = '// Fetching response from ' + url + '...';
      
      try {
        const res = await fetch(url);
        const data = await res.json();
        document.getElementById('resStatus').innerText = 'Status: ' + res.status + ' OK';
        document.getElementById('resStatus').className = 'text-emerald-400 font-bold';
        document.getElementById('jsonViewer').innerText = JSON.stringify(data, null, 2);
      } catch (err) {
        document.getElementById('resStatus').innerText = 'Error';
        document.getElementById('resStatus').className = 'text-red-400 font-bold';
        document.getElementById('jsonViewer').innerText = '// Error fetching: ' + err.message;
      }
    }

    // Run stats test on initial load
    window.onload = () => testEndpoint('/api/v1/stats');
  </script>
</body>
</html>

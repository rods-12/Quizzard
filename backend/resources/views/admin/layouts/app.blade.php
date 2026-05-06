<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ auth()->check() && auth()->user()->role === 'superadmin' ? 'Quizzard SuperAdmin' : 'Quizzard Admin' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @php $isSuperAdmin = auth()->check() && auth()->user()->role === 'superadmin'; @endphp

    @if($isSuperAdmin)
    {{-- Global SuperAdmin select styling — placed in <head> so it wins over Tailwind --}}
    <style>
        .sa-select,
        select.sa-select {
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            background-color: #1e2433 !important;
            border-color: rgba(255,255,255,0.08) !important;
            color: #94a3b8 !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 12px center !important;
            padding-right: 36px !important;
        }
        .sa-select:focus,
        select.sa-select:focus {
            outline: none !important;
            border-color: rgba(99,102,241,0.5) !important;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15) !important;
        }
        .sa-select option,
        select.sa-select option {
            background-color: #1e2433 !important;
            color: #e2e8f0 !important;
        }
    </style>
    @endif
</head>

@php
    $isSuperAdmin = auth()->check() && auth()->user()->role === 'superadmin';
@endphp

@if($isSuperAdmin)
{{-- ============================================================ --}}
{{-- SUPERADMIN BODY - deep navy dark theme                       --}}
{{-- ============================================================ --}}
<body style="background:#0f1117; color:#e2e8f0; min-height:100vh;">
    <div class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">

        <aside style="background:linear-gradient(180deg,#0a0c12 0%,#0d1018 50%,#0a0c12 100%); border-right:1px solid rgba(255,255,255,0.06);" class="shadow-2xl">
            <div class="flex h-full flex-col">

                <div class="px-6 py-6" style="border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="flex items-center justify-center w-6 h-6 rounded-md" style="background:linear-gradient(135deg,#6366f1,#818cf8);">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                            </svg>
                        </div>
                        <span class="text-xs font-bold tracking-widest uppercase" style="color:#818cf8;">SuperAdmin</span>
                    </div>
                    <h1 class="text-lg font-bold text-white mt-2">Quizzard</h1>
                    <p class="text-xs mt-0.5" style="color:#475569;">Control Panel</p>
                </div>

                <nav class="flex-1 px-3 py-5 space-y-1">
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150"
                       style="{{ request()->routeIs('admin.dashboard*') ? 'background:rgba(99,102,241,0.15);color:#a5b4fc;border-left:3px solid #6366f1;' : 'color:#64748b;border-left:3px solid transparent;' }}"
                       @if(!request()->routeIs('admin.dashboard*'))
                           onmouseover="this.style.background='rgba(255,255,255,0.04)';this.style.color='#cbd5e1';"
                           onmouseout="this.style.background='transparent';this.style.color='#64748b';"
                       @endif>
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Menu Dashboard
                    </a>

                    <a href="{{ route('admin.profile') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150"
                       style="{{ request()->routeIs('admin.profile*') ? 'background:rgba(99,102,241,0.15);color:#a5b4fc;border-left:3px solid #6366f1;' : 'color:#64748b;border-left:3px solid transparent;' }}"
                       @if(!request()->routeIs('admin.profile*'))
                           onmouseover="this.style.background='rgba(255,255,255,0.04)';this.style.color='#cbd5e1';"
                           onmouseout="this.style.background='transparent';this.style.color='#64748b';"
                       @endif>
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Profile
                    </a>

                    <a href="{{ route('admin.classes') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150"
                       style="{{ request()->routeIs('admin.classes*') ? 'background:rgba(99,102,241,0.15);color:#a5b4fc;border-left:3px solid #6366f1;' : 'color:#64748b;border-left:3px solid transparent;' }}"
                       @if(!request()->routeIs('admin.classes*'))
                           onmouseover="this.style.background='rgba(255,255,255,0.04)';this.style.color='#cbd5e1';"
                           onmouseout="this.style.background='transparent';this.style.color='#64748b';"
                       @endif>
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        Classes
                    </a>
                </nav>

                <div class="px-3 pb-5" style="border-top:1px solid rgba(255,255,255,0.06);">
                    <div class="mt-4 mb-3 px-3 py-3 rounded-lg" style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);">
                        <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs truncate mt-0.5" style="color:#475569;">{{ auth()->user()->email }}</p>
                    </div>
                    <form action="{{ route('admin.logout') }}" method="POST" id="sa-logout-form">
                        @csrf
                        <button type="submit" id="sa-logout-btn"
                                class="w-full rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-150"
                                style="background:rgba(239,68,68,0.1);color:#f87171;border:1px solid rgba(239,68,68,0.2);"
                                onmouseover="if(!this.disabled){this.style.background='rgba(239,68,68,0.18)';}"
                                onmouseout="if(!this.disabled){this.style.background='rgba(239,68,68,0.1)';}">
                            <span id="sa-logout-label" class="flex items-center justify-center gap-2">
                                <span id="sa-logout-spinner" class="hidden h-4 w-4 animate-spin rounded-full border-2 border-red-400 border-t-transparent"></span>
                                <span id="sa-logout-text">Logout</span>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="min-w-0 p-4 sm:p-6 lg:p-8" style="background:#0f1117;">
            @if(session('success'))
                <div id="globalSuccessToast"
                     class="mb-6 flex items-start gap-3 rounded-2xl px-4 py-4"
                     style="background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.2);color:#a5b4fc;">
                    <div class="mt-0.5">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.172 7.707 8.879a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold">Login Successful</p>
                        <p class="text-sm opacity-80">{{ session('success') }}</p>
                    </div>
                    <button type="button" onclick="document.getElementById('globalSuccessToast').remove()" class="opacity-60 hover:opacity-100 transition">×</button>
                </div>
                <script>setTimeout(()=>{const t=document.getElementById('globalSuccessToast');if(t)t.remove();},3500);</script>
            @endif
            @yield('content')
        </main>
    </div>
    @stack('modals')
    @stack('scripts')

    {{-- SuperAdmin logout loading state --}}
    <script>
        (function () {
            var form = document.getElementById('sa-logout-form');
            var btn  = document.getElementById('sa-logout-btn');
            var spinner = document.getElementById('sa-logout-spinner');
            var text = document.getElementById('sa-logout-text');
            if (!form || !btn) return;
            form.addEventListener('submit', function () {
                btn.disabled = true;
                btn.style.opacity = '0.7';
                btn.style.cursor = 'not-allowed';
                if (spinner) spinner.classList.remove('hidden');
                if (text)    text.textContent = 'Logging out...';
            });
        })();
    </script>
</body>

@else
{{-- ============================================================ --}}
{{-- ADMIN BODY - 100% original, zero changes                     --}}
{{-- ============================================================ --}}
<body class="min-h-screen bg-slate-100 text-slate-800">
    <div class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
        <aside class="bg-slate-900 text-white shadow-2xl">
            <div class="flex h-full flex-col">
                <div class="border-b border-slate-800 px-6 py-6">
                    <h1 class="text-2xl font-bold tracking-wide">Quizzard Admin</h1>
                    <p class="mt-1 text-sm text-slate-400">Admin Management Panel</p>
                </div>
                <nav class="flex-1 space-y-2 px-4 py-6">
                    <a href="{{ route('admin.dashboard') }}"
                       class="block rounded-xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('admin.dashboard*') ? 'bg-blue-600 text-white' : 'text-slate-200 hover:bg-slate-800' }}">
                        Menu Dashboard
                    </a>
                    <a href="{{ route('admin.profile') }}"
                       class="block rounded-xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('admin.profile*') ? 'bg-blue-600 text-white' : 'text-slate-200 hover:bg-slate-800' }}">
                        Profile
                    </a>
                    <a href="{{ route('admin.classes') }}"
                       class="block rounded-xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('admin.classes*') ? 'bg-blue-600 text-white' : 'text-slate-200 hover:bg-slate-800' }}">
                        Classes
                    </a>
                </nav>
                <div class="border-t border-slate-800 p-4">
                    <form action="{{ route('admin.logout') }}" method="POST" id="admin-logout-form">
                        @csrf
                        <button type="submit" id="admin-logout-btn"
                                class="w-full rounded-xl bg-red-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-red-700 disabled:opacity-70 disabled:cursor-not-allowed">
                            <span id="admin-logout-label" class="flex items-center justify-center gap-2">
                                <span id="admin-logout-spinner" class="hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                                <span id="admin-logout-text">Logout</span>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="min-w-0 p-4 sm:p-6 lg:p-8">
            @if(session('success'))
                <div id="globalSuccessToast" class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-emerald-800 shadow-sm">
                    <div class="mt-0.5">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.172 7.707 8.879a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold">Login Successful</p>
                        <p class="text-sm">{{ session('success') }}</p>
                    </div>
                    <button type="button" onclick="document.getElementById('globalSuccessToast').remove()" class="text-emerald-700 transition hover:text-emerald-900">×</button>
                </div>
                <script>setTimeout(()=>{const t=document.getElementById('globalSuccessToast');if(t)t.remove();},3500);</script>
            @endif
            @yield('content')
        </main>
    </div>
    @stack('modals')
    @stack('scripts')

    {{-- Admin logout loading state --}}
    <script>
        (function () {
            var form    = document.getElementById('admin-logout-form');
            var btn     = document.getElementById('admin-logout-btn');
            var spinner = document.getElementById('admin-logout-spinner');
            var text    = document.getElementById('admin-logout-text');
            if (!form || !btn) return;
            form.addEventListener('submit', function () {
                btn.disabled = true;
                if (spinner) spinner.classList.remove('hidden');
                if (text)    text.textContent = 'Logging out...';
            });
        })();
    </script>
</body>
@endif

</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzard Admin</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --admin-sidebar-width: 280px;
        }

        .admin-shell {
            min-height: 100vh;
            background: #f1f5f9;
        }

        .admin-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 40;
            width: var(--admin-sidebar-width);
            min-height: 100vh;
            max-height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .admin-main {
            min-height: 100vh;
            width: calc(100% - var(--admin-sidebar-width));
            margin-left: var(--admin-sidebar-width);
        }

        @media (max-width: 767px) {
            :root {
                --admin-sidebar-width: 240px;
            }

            .admin-main {
                width: calc(100% - var(--admin-sidebar-width));
            }
        }
    </style>
</head>

<body class="min-h-screen overflow-x-hidden bg-slate-100 text-slate-800">
    @php
        $authAdmin = auth()->user();
        $panelLabel = $authAdmin && $authAdmin->role === 'superadmin'
            ? 'SuperAdmin Management Panel'
            : 'Admin Management Panel';
    @endphp

    <div class="admin-shell">

        <aside class="admin-sidebar bg-slate-900 text-white shadow-2xl">
            <div class="flex h-full flex-col">

                <div class="border-b border-slate-800 px-5 py-5">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-2xl px-1 py-1 transition hover:bg-slate-800/60">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/95 p-1.5 shadow-md ring-1 ring-white/10">
                            <img src="{{ asset('images/quizzard-logo.png') }}" alt="Quizzard" class="max-h-full max-w-full object-contain">
                        </span>
                        <span class="min-w-0">
                            <span class="block text-base font-bold leading-tight tracking-wide">Quizzard</span>
                            <span class="mt-1 block text-xs font-medium leading-snug text-slate-400">{{ $panelLabel }}</span>
                        </span>
                    </a>
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
                {{-- Analytics --}}
                    <div class="pt-2">
                        <a href="{{ route('admin.analytics.overview') }}"
                           class="block rounded-xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('admin.analytics*') ? 'bg-blue-600 text-white' : 'text-slate-200 hover:bg-slate-800' }}">
                             Analytics Dashboard
                        </a>
                    </div>
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

        <main class="admin-main min-w-0 p-4 sm:p-6 lg:p-8">
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
    <div id="globalPageLoadingOverlay" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-slate-950/55 backdrop-blur-sm">
        <div class="flex min-w-[300px] flex-col items-center justify-center rounded-3xl bg-white px-8 py-7 shadow-2xl ring-1 ring-slate-200">
            <div class="h-12 w-12 animate-spin rounded-full border-4 border-blue-200 border-t-blue-700"></div>
            <p id="pageLoadingText" class="mt-5 text-sm font-semibold text-slate-700">Loading...</p>
        </div>
    </div>
    @stack('modals')
    @stack('scripts')

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

        (function () {
            var overlay = document.getElementById('globalPageLoadingOverlay');
            var label = document.getElementById('pageLoadingText');
            if (!overlay) return;

            window.showPageLoadingOverlay = function (message) {
                if (label) label.textContent = message || 'Loading...';
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            };

            window.hidePageLoadingOverlay = function () {
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };

            document.addEventListener('click', function (event) {
                var link = event.target.closest('a[href]');
                if (!link || event.defaultPrevented) return;
                if (link.target && link.target !== '_self') return;
                if (link.hasAttribute('download') || link.dataset.noLoading === 'true') return;
                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

                var href = link.getAttribute('href') || '';
                if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;
                if (link.closest('[data-no-page-loading]')) return;

                window.showPageLoadingOverlay(link.dataset.loadingText || 'Loading page...');
            });

            document.addEventListener('submit', function (event) {
                var form = event.target;
                if (!form || form.dataset.noLoading === 'true') return;
                window.showPageLoadingOverlay(form.dataset.loadingText || 'Applying changes...');
            });

            window.addEventListener('pageshow', window.hidePageLoadingOverlay);
        })();
    </script>
</body>

</html>

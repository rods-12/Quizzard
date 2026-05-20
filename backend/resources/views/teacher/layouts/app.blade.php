<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Quizzard — Teacher')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,300&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        /* ── Design tokens ──────────────────────────────────────── */
        :root {
            --bg:          #FAF6EC;
            --surface:     #FFFFFF;
            --surface-2:   #F3EEF9;
            --surface-3:   #EDE7F2;
            --border:      rgba(91,42,155,0.10);
            --border-md:   rgba(91,42,155,0.18);
            --text:        #1F1235;
            --text-2:      #6B5A8A;
            --text-3:      #A99BC4;
            --accent:      #F2C94C;
            --accent-dim:  #E0A93B;
            --accent-bg:   rgba(242,201,76,0.10);
            --accent-bg2:  rgba(242,201,76,0.18);
            --danger:      #EF4444;
            --warn:        #F59E0B;
            --info:        #A14BC9;
            --radius:      14px;
            --radius-sm:   9px;
            --sidebar-w:   252px;
            --font:        'DM Sans', sans-serif;
            --mono:        'DM Mono', monospace;
            --shadow-card: 0 1px 3px rgba(42,18,71,0.08), 0 8px 24px rgba(42,18,71,0.12);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            line-height: 1.6;
        }

        /* ── Scrollbar ──────────────────────────────────────────── */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(91,42,155,0.18); border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(91,42,155,0.32); }

        /* ── App shell ──────────────────────────────────────────── */
        /*
         * The app-shell is a flex row.
         * On desktop: #sidebar is a sticky flex child (naturally pushes <main> right).
         * On mobile:  #sidebar becomes fixed + slides in; main is full-width.
         */
        .app-shell {
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ────────────────────────────────────────────── */
        #sidebar {
            width: var(--sidebar-w);
            min-width: var(--sidebar-w);
            flex-shrink: 0;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: sticky;
            top: 0;
            z-index: 40;
            transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Mobile: sidebar goes fixed + off-screen */
        @media (max-width: 1023px) {
            #sidebar {
                position: fixed;
                left: 0;
                top: 0;
                transform: translateX(-100%);
            }
            #sidebar.is-open {
                transform: translateX(0);
            }
        }

        /* Desktop: sidebar is always visible; no transform, no fixed */
        @media (min-width: 1024px) {
            #mobile-header   { display: none !important; }
            #sidebar-overlay { display: none !important; }
        }

        /* ── Nav links ──────────────────────────────────────────── */
        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: var(--radius-sm);
            font-size: 13.5px;
            font-weight: 500;
            color: var(--text-2);
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
            letter-spacing: -0.01em;
        }
        .nav-link:hover { background: var(--surface-3); color: var(--text); }
        .nav-link.active {
            background: var(--surface-3);
            color: #5B2A9B;
            box-shadow: inset 0 0 0 1px rgba(91,42,155,0.18);
        }
        .nav-link svg { opacity: 0.6; flex-shrink: 0; transition: opacity 0.15s; }
        .nav-link:hover svg, .nav-link.active svg { opacity: 1; }

        /* ── Card ───────────────────────────────────────────────── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-card);
        }
        .card-header {
            padding: 20px 24px 18px;
            border-bottom: 1px solid var(--border);
        }

        /* ── Badge ──────────────────────────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 9px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.01em;
        }
        .badge-green  { background: rgba(34,197,94,0.10);   color: #22C55E; border: 1px solid rgba(34,197,94,0.22); }
        .badge-amber  { background: rgba(242,201,76,0.12);  color: #E0A93B; border: 1px solid rgba(242,201,76,0.28); }
        .badge-rose   { background: rgba(239,68,68,0.10);   color: #EF4444; border: 1px solid rgba(239,68,68,0.22); }
        .badge-sky    { background: rgba(161,75,201,0.10);  color: #A14BC9; border: 1px solid rgba(161,75,201,0.22); }
        .badge-slate  { background: rgba(169,155,196,0.12); color: #A99BC4; border: 1px solid rgba(169,155,196,0.22); }

        /* ── Score colours ──────────────────────────────────────── */
        .score-high  { background: rgba(34,197,94,0.10);   color: #22C55E; border: 1px solid rgba(34,197,94,0.2); }
        .score-mid   { background: rgba(242,201,76,0.12);  color: #E0A93B; border: 1px solid rgba(242,201,76,0.25); }
        .score-low   { background: rgba(239,68,68,0.10);   color: #EF4444; border: 1px solid rgba(239,68,68,0.2); }
        .score-none  { background: var(--surface-3); color: var(--text-3); border: 1px solid var(--border); }

        /* ── Buttons ─────────────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 9px 18px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            letter-spacing: -0.01em;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.15s;
            white-space: nowrap;
            font-family: var(--font);
        }
        .btn-primary { background: #F2C94C; color: #1F1235; }
        .btn-primary:hover { background: #E0A93B; transform: translateY(-1px); box-shadow: 0 4px 14px rgba(242,201,76,0.35); }
        .btn-secondary { background: var(--surface-3); color: var(--text-2); border: 1px solid var(--border-md); }
        .btn-secondary:hover { background: var(--surface-2); color: var(--text); }
        .btn-ghost { background: transparent; color: var(--text-2); border: 1px solid var(--border-md); }
        .btn-ghost:hover { background: var(--surface-3); color: var(--text); }
        .btn-sm { padding: 6px 13px; font-size: 12px; border-radius: 7px; }

        /* ── Chip ────────────────────────────────────────────────── */
        .chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 7px;
            font-size: 11.5px;
            font-weight: 500;
            background: var(--surface-3);
            color: var(--text-2);
            border: 1px solid var(--border);
        }

        /* ── Attention panels ────────────────────────────────────── */
        .attention-amber { background: rgba(242,201,76,0.06);  border-color: rgba(242,201,76,0.25); }
        .attention-rose  { background: rgba(239,68,68,0.05);   border-color: rgba(239,68,68,0.2); }
        .attention-sky   { background: rgba(161,75,201,0.06);  border-color: rgba(161,75,201,0.2); }
        .attention-slate { background: rgba(169,155,196,0.06); border-color: rgba(169,155,196,0.18); }

        /* ── Utilities ───────────────────────────────────────────── */
        .num     { font-family: var(--mono); font-variant-numeric: tabular-nums; }
        .divider { border-top: 1px solid var(--border); }

        /* ── Overlay ─────────────────────────────────────────────── */
        #sidebar-overlay {
            backdrop-filter: blur(3px);
            background: rgba(42,18,71,0.45);
        }
    </style>
</head>

<body>

    {{-- Mobile header — hidden on desktop via @media --}}
    <header id="mobile-header"
            style="display:flex; align-items:center; justify-content:space-between;
                   padding:12px 16px; position:sticky; top:0; z-index:50;
                   background:var(--surface); border-bottom:1px solid var(--border);">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="height:28px; width:28px; border-radius:8px;
                        background:linear-gradient(135deg,#5B2A9B,#3A1A6B);
                        display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg style="width:15px;height:15px;" fill="none" stroke="#F2C94C" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <span style="font-size:14px; font-weight:700; color:var(--text); letter-spacing:-0.02em;">Quizzard</span>
        </div>
        <button id="mobile-menu-btn"
                style="padding:7px; border-radius:8px; border:none; background:transparent; color:var(--text-2); cursor:pointer; line-height:0;"
                onmouseenter="this.style.background='var(--surface-3)'"
                onmouseleave="this.style.background='transparent'">
            <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </header>

    {{-- Overlay (mobile only) --}}
    <div id="sidebar-overlay" class="fixed inset-0 z-30 hidden opacity-0 transition-opacity"></div>

    {{-- App shell: sidebar sits as a flex child, main fills the rest --}}
    <div class="app-shell">

        <aside id="sidebar">

            {{-- Brand --}}
            <div style="padding:22px 20px 20px; border-bottom:1px solid var(--border); flex-shrink:0;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="height:32px; width:32px; border-radius:10px;
                                background:linear-gradient(135deg,#5B2A9B,#3A1A6B);
                                display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <svg style="width:17px;height:17px;" fill="none" stroke="#F2C94C" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <div>
                        <p style="font-size:14px; font-weight:700; color:var(--text); letter-spacing:-0.02em; line-height:1.3;">Quizzard</p>
                        <p style="font-size:10px; font-weight:600; color:var(--text-3); letter-spacing:0.1em; text-transform:uppercase;">Teacher</p>
                    </div>
                </div>
            </div>

            {{-- Nav --}}
            <nav style="flex:1; overflow-y:auto; padding:14px 12px; display:flex; flex-direction:column; gap:2px;">
                @php
                    $navItems = [
                        ['route' => 'teacher.dashboard',        'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['route' => 'teacher.reports.classes',  'label' => 'Classes',   'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                        ['route' => 'teacher.reports.quizzes',  'label' => 'Quizzes',   'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                        ['route' => 'teacher.reports.students', 'label' => 'Students',  'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                        ['route' => 'teacher.analytics.global', 'label' => 'Analytics', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ];
                @endphp

                @foreach ($navItems as $item)
                    <a href="{{ route($item['route']) }}"
                       class="nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}">
                        <svg style="width:16px;height:16px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                        </svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach

                <div style="padding:14px 12px 6px;">
                    <p style="font-size:10px; font-weight:600; color:var(--text-3); text-transform:uppercase; letter-spacing:0.08em;">Reports</p>
                </div>
                <a href="{{ route('teacher.reports.students.export') }}" class="nav-link">
                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export Students
                </a>
            </nav>

            {{-- User --}}
            <div style="padding:12px; border-top:1px solid var(--border); flex-shrink:0;">
                <div style="display:flex; align-items:center; gap:12px; padding:12px; border-radius:12px;
                            background:var(--surface-2); border:1px solid var(--border); margin-bottom:8px;">
                    <div style="height:32px; width:32px; border-radius:9px; flex-shrink:0;
                                display:flex; align-items:center; justify-content:center;
                                font-size:12px; font-weight:700;
                                background:linear-gradient(135deg,#5B2A9B,#3A1A6B);
                                color:#F2C94C;
                                border:1px solid rgba(91,42,155,0.25);">
                        {{ strtoupper(substr(auth()->user()->name ?? 'T', 0, 1)) }}
                    </div>
                    <div style="min-width:0; flex:1;">
                        <p style="font-size:12px; font-weight:600; color:var(--text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            {{ auth()->user()->name ?? 'Teacher' }}
                        </p>
                        <p style="font-size:10px; color:var(--text-3);">Teacher account</p>
                    </div>
                </div>

                <form action="{{ route('teacher.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm" style="width:100%;">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Sign out
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main content — fills all remaining width because sidebar is a flex child --}}
        <main style="flex:1; min-width:0; overflow:auto;">
            <div style="padding:28px 32px; max-width:1400px; margin:0 auto;">
                @yield('content')
            </div>
        </main>

    </div>{{-- /.app-shell --}}

    <script>
        (function () {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const btn     = document.getElementById('mobile-menu-btn');

            function open() {
                sidebar.classList.add('is-open');
                overlay.classList.remove('hidden');
                requestAnimationFrame(() => overlay.classList.remove('opacity-0'));
            }
            function close() {
                sidebar.classList.remove('is-open');
                overlay.classList.add('opacity-0');
                setTimeout(() => overlay.classList.add('hidden'), 280);
            }

            btn?.addEventListener('click', () =>
                sidebar.classList.contains('is-open') ? close() : open());
            overlay?.addEventListener('click', close);
        })();
    </script>

    @stack('modals')
    @stack('scripts')
    @stack('charts')
</body>
</html>

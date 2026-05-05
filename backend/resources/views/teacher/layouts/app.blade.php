<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzard Teacher</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Critical fix for sticky sidebar behavior */
        .app-layout {
            display: flex;
            min-height: 100vh;
        }

        .app-sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            width: 288px;
            /* w-72 */
            flex-shrink: 0;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .app-main {
            flex: 1;
            min-width: 0;
        }

        /* Custom scrollbar for better UX */
        .app-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .app-sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .app-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .app-sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .nav-link {
            color: white;
        }

        .nav-link:hover {
            background-color: #16a34a;
            /* green-600 */
            color: white;
        }

        /* Active state */
        .nav-link.active {
            background-color: white;
            color: #166534;
            /* green-800 */
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="min-h-screen bg-slate-50">
    <div class="app-layout">
        <!-- Sidebar - Clean green theme -->
        <aside class="app-sidebar bg-green-700 shadow-2xl">
            <div class="flex flex-col min-h-full">
                <!-- Brand Section -->
                <div class="border-b border-green-600 px-6 py-8 flex-shrink-0">
                    <h1 class="text-3xl font-bold tracking-tight text-white">
                        Quizzard Teacher
                    </h1>
                    <p class="mt-2 text-sm font-medium text-green-100">
                        Analytics & Reporting Panel
                    </p>
                </div>

                <!-- Navigation - Fixed contrast -->
                <nav class="flex-shrink-0 space-y-1 px-3 py-6" style="margin-bottom: 300px">
                    @php
                        $navItems = [
                            ['route' => 'teacher.dashboard', 'label' => 'Dashboard'],
                            ['route' => 'teacher.reports.classes', 'label' => 'Classes'],
                            ['route' => 'teacher.reports.quizzes', 'label' => 'Quizzes'],
                            ['route' => 'teacher.reports.students', 'label' => 'Students'],
                        ];
                    @endphp

                    @foreach ($navItems as $item)
                        <a href="{{ route($item['route']) }}"
                            style="margin-top: 30px"
                            class="nav-link block rounded-lg px-4 py-4 text-sm font-medium transition-all duration-200 {{ request()->routeIs($item['route']) ? 'active' : 'text-white' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <!-- Spacer to push user section to bottom -->
                <div class="flex-1 min-h-[20px]"></div>

                <!-- User Section - Clean design -->
                <div class="border-t border-green-600 p-4 flex-shrink-0">
                    <div class="mb-4 rounded-lg bg-green-800 p-3">
                        <p class="text-sm font-medium text-white">{{ auth()->user()->name ?? 'Teacher Name' }}</p>
                        <p class="text-xs text-green-200">Teacher</p>
                    </div>

                    <form action="{{ route('teacher.logout') }}" method="POST" id="logout-form">
                        @csrf
                        <button
                            type="submit"
                            id="logout-btn"
                            class="w-full rounded-lg bg-red-600 px-4 py-3 text-sm font-semibold text-white transition-all duration-200 hover:bg-red-700 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="app-main bg-white">
            <div class="p-6 lg:p-8">
                @yield('content')
            </div>
        </main>
    </div>

    @stack('modals')
    @stack('scripts')

    <script>
        document.getElementById('logout-form').addEventListener('submit', function () {
            const btn = document.getElementById('logout-btn');
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                Logging out...
            `;
        });
    </script>
</body>

</html>

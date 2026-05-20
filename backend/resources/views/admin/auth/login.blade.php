<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzard Admin Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <div class="relative min-h-screen overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.22),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(147,51,234,0.18),_transparent_28%)]"></div>

        <div class="relative z-10 flex min-h-screen items-center justify-center px-4 py-10">
            <div class="grid w-full max-w-6xl overflow-hidden rounded-3xl border border-white/10 bg-white/5 shadow-2xl backdrop-blur-xl lg:grid-cols-2">
                <div class="hidden lg:flex flex-col justify-between border-r border-white/10 bg-slate-900/70 p-10">
                    <div>
                        <div class="flex items-center gap-4">
                            <div class="flex h-28 w-32 items-center justify-center overflow-hidden">
                                <img src="{{ asset('images/quizzard-logo.png') }}" alt="Quizzard" class="h-full w-full scale-125 object-contain drop-shadow-[0_10px_18px_rgba(0,0,0,0.35)]">
                            </div>
                            <div class="inline-flex items-center gap-2 rounded-full border border-blue-400/30 bg-blue-500/10 px-4 py-1.5 text-sm font-medium text-blue-200">
                                Quizzard Management Portal
                            </div>
                        </div>

                        <h1 class="mt-6 text-4xl font-bold leading-tight text-white">
                            Admin &amp; Super Admin Access
                        </h1>

                        <p class="mt-4 max-w-md text-sm leading-7 text-slate-300">
                            Securely manage accounts, activations, classes, and administrative tools from one professional dashboard.
                        </p>
                    </div>

                    <div class="grid gap-4">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm font-semibold text-white">Role-aware access</p>
                            <p class="mt-1 text-sm text-slate-300">
                                Admin and Super Admin accounts are validated before dashboard access.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm font-semibold text-white">Safe authentication flow</p>
                            <p class="mt-1 text-sm text-slate-300">
                                Active-status checking remains intact with your current authentication logic.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 sm:p-8 lg:p-10">
                    <div class="mx-auto w-full max-w-md">
                        <div class="mb-8">
                            <div class="mb-5 flex items-center gap-3">
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">
                                    Quizzard
                                </p>
                            </div>
                            <h2 class="mt-2 text-3xl font-bold text-slate-900">
                                Sign in
                            </h2>
                            <p class="mt-2 text-sm text-slate-500">
                                Enter your admin credentials to continue.
                            </p>
                        </div>

                        @if($errors->any())
                            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form id="adminLoginForm" action="{{ route('admin.login.submit') }}" method="POST" class="space-y-5">
                            @csrf

                            <div>
                                <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">
                                    Email Address
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 placeholder:text-slate-400 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                    placeholder="Enter your email"
                                >
                            </div>

                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <label for="password" class="block text-sm font-semibold text-slate-700">
                                        Password
                                    </label>

                                    <button
                                        type="button"
                                        id="togglePasswordBtn"
                                        class="text-xs font-medium text-blue-600 transition hover:text-blue-700"
                                    >
                                        Show
                                    </button>
                                </div>

                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 placeholder:text-slate-400 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                    placeholder="Enter your password"
                                >
                            </div>

                            <div class="flex items-center justify-between">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                    <input
                                        type="checkbox"
                                        name="remember"
                                        value="1"
                                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    >
                                    Remember me
                                </label>

                                <span class="text-xs text-slate-400">
                                    Authorized access only
                                </span>
                            </div>

                            <button
                                id="loginSubmitBtn"
                                type="submit"
                                class="inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                <svg id="loginSpinner" class="hidden h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <span id="loginSubmitText">Sign In</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('adminLoginForm');
            const submitBtn = document.getElementById('loginSubmitBtn');
            const submitText = document.getElementById('loginSubmitText');
            const spinner = document.getElementById('loginSpinner');
            const passwordInput = document.getElementById('password');
            const togglePasswordBtn = document.getElementById('togglePasswordBtn');

            if (togglePasswordBtn && passwordInput) {
                togglePasswordBtn.addEventListener('click', function () {
                    const isPassword = passwordInput.type === 'password';
                    passwordInput.type = isPassword ? 'text' : 'password';
                    togglePasswordBtn.textContent = isPassword ? 'Hide' : 'Show';
                });
            }

            if (form) {
                form.addEventListener('submit', function () {
                    submitBtn.disabled = true;
                    spinner.classList.remove('hidden');
                    submitText.textContent = 'Signing in...';
                });
            }
        });
    </script>
</body>
</html>

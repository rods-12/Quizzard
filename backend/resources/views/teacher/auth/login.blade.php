<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="w-full max-w-md bg-white rounded-xl shadow p-8">
        <h1 class="text-2xl font-bold mb-6 text-center">Teacher Login</h1>

        @if($errors->any())
            <div class="mb-4 rounded bg-red-100 text-red-700 px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('teacher.login.submit') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block mb-1 font-medium">Email</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="w-full border rounded px-4 py-2"
                >
            </div>

            <div>
                <label class="block mb-1 font-medium">Password</label>
                <input
                    type="password"
                    name="password"
                    class="w-full border rounded px-4 py-2"
                >
            </div>

            <button
                id="login-btn"
                class="w-full bg-green-600 text-white rounded px-4 py-2 hover:bg-green-700 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed"
            >
                Login
            </button>
        </form>
    </div>


    <script>
        document.querySelector('form').addEventListener('submit', function () {
            const btn = document.getElementById('login-btn');
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                Logging in...
            `;
        });
    </script>
</body>
</html>
